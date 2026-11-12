<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConfigurarTelegramTest extends TestCase
{
    private string $pasta;

    protected function setUp(): void
    {
        parent::setUp();

        // Um .env de mentira: o teste nunca toca no .env do projeto.
        $this->pasta = sys_get_temp_dir().'/monitor-de-vagas-'.uniqid();
        mkdir($this->pasta);
        file_put_contents($this->pasta.'/.env', "APP_NAME=Monitor\nVAGAS_AVISOS_ATIVOS=false\n");
        $this->app->useEnvironmentPath($this->pasta);
    }

    protected function tearDown(): void
    {
        @unlink($this->pasta.'/.env');
        @rmdir($this->pasta);
        parent::tearDown();
    }

    private function env(): string
    {
        return (string) file_get_contents($this->pasta.'/.env');
    }

    private function atualizacao(string $texto, string $tipo = 'private', int $chat = 777): array
    {
        return ['message' => ['text' => $texto, 'chat' => ['id' => $chat, 'type' => $tipo]]];
    }

    public function test_grava_token_e_chat_no_env_e_manda_mensagem_de_teste(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'meu_monitor_bot']]),
            'api.telegram.org/*/getUpdates' => Http::response(['ok' => true, 'result' => [
                $this->atualizacao('/start'),
                // O mais recente é de um grupo: tem de ser ignorado.
                $this->atualizacao('/start', 'group', 111),
            ]]),
            'api.telegram.org/*/sendMessage' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);

        $this->artisan('vagas:configurar-telegram')
            ->expectsQuestion('Token do bot, copiado da conversa com o @BotFather (não aparece na tela)', ' 123:abc ')
            ->expectsOutputToContain('@meu_monitor_bot')
            ->expectsConfirmation('Já mandou o /start?', 'yes')
            ->assertSuccessful();

        $this->assertStringContainsString("TELEGRAM_BOT_TOKEN=123:abc\n", $this->env());
        // O chat privado, não o do grupo.
        $this->assertStringContainsString("TELEGRAM_CHAT_ID=777\n", $this->env());
        $this->assertStringContainsString("VAGAS_AVISOS_ATIVOS=true\n", $this->env());
        $this->assertSame(1, substr_count($this->env(), 'VAGAS_AVISOS_ATIVOS='));
        $this->assertStringContainsString('APP_NAME=Monitor', $this->env());
        Http::assertSent(fn (Request $pedido) => str_ends_with($pedido->url(), '/sendMessage') && $pedido['chat_id'] === '777');
    }

    public function test_token_recusado_nao_grava_nada(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'error_code' => 401, 'description' => 'Unauthorized'], 401)]);
        $antes = $this->env();

        $this->artisan('vagas:configurar-telegram')
            ->expectsQuestion('Token do bot, copiado da conversa com o @BotFather (não aparece na tela)', 'errado')
            ->expectsOutputToContain('não reconheceu esse token')
            ->assertFailed();

        $this->assertSame($antes, $this->env());
    }

    public function test_sem_o_start_nao_grava_nada(): void
    {
        Http::fake([
            'api.telegram.org/*/getMe' => Http::response(['ok' => true, 'result' => ['username' => 'meu_monitor_bot']]),
            'api.telegram.org/*/getUpdates' => Http::response(['ok' => true, 'result' => [$this->atualizacao('oi')]]),
        ]);
        $antes = $this->env();

        $this->artisan('vagas:configurar-telegram')
            ->expectsQuestion('Token do bot, copiado da conversa com o @BotFather (não aparece na tela)', '123:abc')
            ->expectsConfirmation('Já mandou o /start?', 'yes')
            ->expectsOutputToContain('Não achei o /start')
            ->assertFailed();

        $this->assertSame($antes, $this->env());
    }
}
