<?php

namespace App\Console\Commands;

use App\Avisos\Telegram;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

/**
 * O caminho do token: do BotFather para o terminal de quem configura, com a
 * digitação escondida, e daí só para o .env. Ele não passa por chat, nota,
 * log nem código.
 */
#[Signature('vagas:configurar-telegram')]
#[Description('Liga os avisos no Telegram: pede o token do bot e descobre o chat')]
class ConfigurarTelegram extends Command
{
    public function handle(): int
    {
        $token = trim((string) $this->secret('Token do bot, copiado da conversa com o @BotFather (não aparece na tela)'));
        $telegram = new Telegram($token);

        try {
            $bot = $telegram->quemSouEu();
        } catch (Throwable) {
            $this->error('O Telegram não reconheceu esse token. Copie de novo da conversa com o @BotFather.');

            return self::FAILURE;
        }

        $this->info("Bot @{$bot['username']} encontrado.");
        $this->line("Agora, no Telegram, abra a conversa com @{$bot['username']} e mande /start.");

        if (! $this->confirm('Já mandou o /start?', true)) {
            return self::FAILURE;
        }

        $chatId = $this->chatDoStart($telegram->atualizacoes());
        if ($chatId === null) {
            $this->error("Não achei o /start. Mande /start para @{$bot['username']} e rode o comando de novo.");

            return self::FAILURE;
        }

        $this->gravarNoEnv([
            'TELEGRAM_BOT_TOKEN' => $token,
            'TELEGRAM_CHAT_ID' => $chatId,
            'VAGAS_AVISOS_ATIVOS' => 'true',
        ]);

        $telegram->enviar($chatId, '✅ Monitor de vagas ligado. As vagas novas que baterem com o seu perfil chegam aqui.');
        $this->info('Pronto: os avisos estão ligados, e uma mensagem de teste foi para o Telegram.');

        return self::SUCCESS;
    }

    /**
     * O chat da mensagem /start mais recente. Só conversa privada: o bot não
     * pode ser configurado por quem o adicionou num grupo.
     *
     * @param  list<array<string, mixed>>  $atualizacoes
     */
    private function chatDoStart(array $atualizacoes): ?string
    {
        foreach (array_reverse($atualizacoes) as $atualizacao) {
            $mensagem = $atualizacao['message'] ?? [];

            if (str_starts_with($mensagem['text'] ?? '', '/start') && ($mensagem['chat']['type'] ?? null) === 'private') {
                return (string) $mensagem['chat']['id'];
            }
        }

        return null;
    }

    /**
     * @param  array<string, string>  $valores
     */
    private function gravarNoEnv(array $valores): void
    {
        $arquivo = $this->laravel->environmentFilePath();
        $conteudo = (string) @file_get_contents($arquivo);

        foreach ($valores as $chave => $valor) {
            $linha = "{$chave}={$valor}";
            $conteudo = preg_match("/^{$chave}=.*$/m", $conteudo)
                ? preg_replace("/^{$chave}=.*$/m", $linha, $conteudo)
                : rtrim($conteudo, "\r\n")."\n{$linha}\n";
        }

        file_put_contents($arquivo, $conteudo);
    }
}
