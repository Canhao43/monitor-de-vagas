<?php

namespace Tests\Feature;

use App\Models\Vaga;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class VerificarVagasCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Só os dois repositórios simulados: Gupy e Remotive têm teste próprio.
        config([
            'vagas.github.repositorios' => ['exemplo/um', 'exemplo/dois'],
            'vagas.gupy.ativa' => false,
            'vagas.remotive.ativa' => false,
        ]);
    }

    private function issue(int $numero, string $titulo): array
    {
        return ['number' => $numero, 'title' => $titulo, 'html_url' => "https://github.com/exemplo/um/issues/{$numero}", 'created_at' => '2026-11-10T12:00:00Z', 'labels' => []];
    }

    public function test_mostra_as_vagas_novas_e_termina_com_sucesso(): void
    {
        Http::fake([
            'api.github.com/repos/exemplo/um/*' => Http::response([$this->issue(1, '[Remoto] Desenvolvedor PHP Pleno')]),
            'api.github.com/repos/exemplo/dois/*' => Http::response([]),
        ]);

        $this->artisan('vagas:verificar')
            ->expectsOutputToContain('1 vaga(s) nova(s)')
            ->expectsOutputToContain('Desenvolvedor PHP Pleno')
            ->assertSuccessful();

        $this->assertDatabaseCount('vagas', 1);
    }

    public function test_uma_fonte_fora_do_ar_avisa_mas_nao_falha_o_comando(): void
    {
        Http::fake([
            'api.github.com/repos/exemplo/um/*' => Http::response([], 500),
            'api.github.com/repos/exemplo/dois/*' => Http::response([]),
        ]);

        $this->artisan('vagas:verificar')
            ->expectsOutputToContain('A fonte github:exemplo/um falhou')
            ->assertSuccessful();
    }

    public function test_falha_quando_todas_as_fontes_falham(): void
    {
        Http::fake(['api.github.com/*' => Http::response([], 500)]);

        $this->artisan('vagas:verificar')->assertFailed();
    }

    public function test_simulacao_nao_grava(): void
    {
        Http::fake([
            'api.github.com/repos/exemplo/um/*' => Http::response([$this->issue(1, '[Remoto] Desenvolvedor PHP Pleno')]),
            'api.github.com/repos/exemplo/dois/*' => Http::response([]),
        ]);

        $this->artisan('vagas:verificar --simular')->expectsOutputToContain('Simulação: 1 vaga(s) nova(s)')->assertSuccessful();

        $this->assertDatabaseCount('vagas', 0);
    }

    private function comAvisosLigados(): void
    {
        config([
            'vagas.avisos.ativos' => true,
            'vagas.avisos.telegram.token' => 'token-de-teste',
            'vagas.avisos.telegram.chat_id' => '12345',
        ]);
    }

    public function test_com_avisos_ligados_avisa_as_novas_no_telegram(): void
    {
        $this->comAvisosLigados();
        Http::fake([
            'api.github.com/repos/exemplo/um/*' => Http::response([$this->issue(1, '[Remoto] Desenvolvedor PHP Pleno')]),
            'api.github.com/repos/exemplo/dois/*' => Http::response([]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);

        $this->artisan('vagas:verificar')->expectsOutputToContain('1 vaga(s) avisada(s) no Telegram')->assertSuccessful();

        $this->assertDatabaseMissing('vagas', ['avisada_em' => null]);
    }

    public function test_simulacao_nunca_avisa(): void
    {
        $this->comAvisosLigados();
        // Uma vaga pendente de uma execução anterior: sem ela, não haveria o
        // que avisar, e o teste passaria mesmo se a simulação avisasse.
        Vaga::create(['fonte' => 'gupy', 'id_externo' => '9', 'titulo' => 'Dev PHP', 'url' => 'https://exemplo.test/9', 'vista_em' => now()]);
        // O Telegram responde: se o comando tentasse avisar, o pedido ficaria
        // registrado e o assertNotSent pegaria.
        Http::fake([
            'api.github.com/repos/exemplo/um/*' => Http::response([$this->issue(1, '[Remoto] Desenvolvedor PHP Pleno')]),
            'api.github.com/repos/exemplo/dois/*' => Http::response([]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);

        $this->artisan('vagas:verificar --simular')->assertSuccessful();
        Http::assertNotSent(fn ($pedido) => str_contains($pedido->url(), 'telegram'));
        $this->assertDatabaseHas('vagas', ['id_externo' => '9', 'avisada_em' => null]);
    }

    public function test_com_avisos_desligados_grava_e_deixa_o_aviso_pendente(): void
    {
        // Configurado, mas desligado: é a chave que decide.
        config(['vagas.avisos.telegram.token' => 'token-de-teste', 'vagas.avisos.telegram.chat_id' => '12345']);
        Http::fake([
            'api.github.com/repos/exemplo/um/*' => Http::response([$this->issue(1, '[Remoto] Desenvolvedor PHP Pleno')]),
            'api.github.com/repos/exemplo/dois/*' => Http::response([]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]]),
        ]);

        $this->artisan('vagas:verificar')->assertSuccessful();

        $this->assertDatabaseHas('vagas', ['id_externo' => '1', 'avisada_em' => null]);
        Http::assertNotSent(fn ($pedido) => str_contains($pedido->url(), 'telegram'));
    }

    public function test_cada_execucao_deixa_uma_linha_no_log(): void
    {
        Http::fake([
            'api.github.com/repos/exemplo/um/*' => Http::response([$this->issue(1, '[Remoto] Desenvolvedor PHP Pleno')]),
            'api.github.com/repos/exemplo/dois/*' => Http::response([], 500),
        ]);
        Log::spy();

        $this->artisan('vagas:verificar')->assertSuccessful();

        Log::shouldHaveReceived('info')->once()->withArgs(fn ($mensagem, $contexto) => $mensagem === 'Verificação de vagas'
            && $contexto['novas'] === 1
            && $contexto['falhas'] === ['github:exemplo/dois']);
    }

    public function test_simulacao_nao_deixa_linha_no_log(): void
    {
        Http::fake(['api.github.com/*' => Http::response([])]);
        Log::spy();

        $this->artisan('vagas:verificar --simular')->assertSuccessful();

        Log::shouldNotHaveReceived('info');
    }
}
