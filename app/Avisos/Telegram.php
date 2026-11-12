<?php

namespace App\Avisos;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * O pedaço da API de bots do Telegram que o monitor usa. O token vai na URL
 * (é assim que a API funciona), então ele nunca aparece em mensagem de erro.
 */
final class Telegram
{
    public function __construct(private readonly string $token) {}

    /**
     * @return array<string, mixed> o bot, como o Telegram o descreve
     */
    public function quemSouEu(): array
    {
        return $this->chamar('getMe');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function atualizacoes(): array
    {
        return $this->chamar('getUpdates', ['timeout' => 0]);
    }

    public function enviar(string $chatId, string $html): void
    {
        $this->chamar('sendMessage', [
            'chat_id' => $chatId,
            'text' => $html,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function chamar(string $metodo, array $dados = []): mixed
    {
        $resposta = Http::timeout(15)->asJson()->post("https://api.telegram.org/bot{$this->token}/{$metodo}", $dados);
        $corpo = $resposta->json() ?? [];

        if (($corpo['ok'] ?? false) === true) {
            return $corpo['result'];
        }

        if (($corpo['error_code'] ?? $resposta->status()) === 429) {
            throw new TelegramPediuEspera((int) ($corpo['parameters']['retry_after'] ?? 60));
        }

        // A mensagem do Telegram, nunca a URL: ela contém o token.
        throw new RuntimeException('Telegram recusou: '.($corpo['description'] ?? "HTTP {$resposta->status()}"));
    }
}
