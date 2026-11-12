<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
}
