<?php

namespace Tests\Feature;

use App\Vagas\Fontes\RepositorioDeVagasDoGithub;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RepositorioDeVagasDoGithubTest extends TestCase
{
    private const URL = 'api.github.com/repos/exemplo/vagas/issues*';

    /**
     * Formato real da API de issues do GitHub, com dados fictícios.
     *
     * @return list<array<string, mixed>>
     */
    private function issues(): array
    {
        return [
            ['number' => 10, 'title' => '[Remoto] Desenvolvedor PHP Pleno - Empresa Fictícia', 'html_url' => 'https://github.com/exemplo/vagas/issues/10', 'created_at' => '2026-11-10T12:00:00Z', 'labels' => [['name' => 'Pleno']]],
            ['number' => 11, 'title' => 'Back-end Laravel - Outra Empresa', 'html_url' => 'https://github.com/exemplo/vagas/issues/11', 'created_at' => '2026-11-09T12:00:00Z', 'labels' => [['name' => 'Remoto'], ['name' => 'PJ']]],
            ['number' => 12, 'title' => '[São Paulo] Dev PHP - 100% Presencial', 'html_url' => 'https://github.com/exemplo/vagas/issues/12', 'created_at' => '2026-11-08T12:00:00Z', 'labels' => []],
            ['number' => 13, 'title' => 'Dev PHP', 'html_url' => 'https://github.com/exemplo/vagas/issues/13', 'created_at' => '2026-11-07T12:00:00Z', 'labels' => []],
            ['number' => 14, 'title' => 'docs: atualiza o README', 'html_url' => 'https://github.com/exemplo/vagas/pull/14', 'created_at' => '2026-11-06T12:00:00Z', 'labels' => [], 'pull_request' => ['url' => 'x']],
        ];
    }

    public function test_converte_cada_issue_em_vaga_e_pula_pull_request(): void
    {
        Http::fake([self::URL => Http::response($this->issues(), 200, ['ETag' => '"v1"'])]);

        $vagas = (new RepositorioDeVagasDoGithub('exemplo/vagas'))->buscar();

        $this->assertSame(['10', '11', '12', '13'], array_map(fn ($v) => $v->idExterno, $vagas));
        $this->assertSame('github:exemplo/vagas', $vagas[0]->fonte);
        $this->assertSame('https://github.com/exemplo/vagas/issues/10', $vagas[0]->url);
        $this->assertSame('2026-11-10', $vagas[0]->publicadaEm->toDateString());
        $this->assertSame(['Remoto', 'PJ'], $vagas[1]->etiquetas);
    }

    public function test_descobre_se_e_remota_pelo_titulo_ou_pelo_rotulo(): void
    {
        Http::fake([self::URL => Http::response($this->issues())]);

        $remota = array_map(fn ($v) => $v->remota, (new RepositorioDeVagasDoGithub('exemplo/vagas'))->buscar());

        // [Remoto] no título; rótulo Remoto; "Presencial"; sem pista nenhuma.
        $this->assertSame([true, true, false, null], $remota);
    }

    public function test_depois_de_confirmar_a_leitura_a_proxima_busca_e_condicional(): void
    {
        Http::fake([self::URL => Http::sequence()
            ->push($this->issues(), 200, ['ETag' => '"v1"'])
            ->push(null, 304)]);

        $fonte = new RepositorioDeVagasDoGithub('exemplo/vagas');
        $fonte->buscar();
        $fonte->confirmarLeitura();

        // 304: nada mudou desde a última leitura, então nenhuma vaga nova.
        $this->assertSame([], $fonte->buscar());
        Http::assertSent(fn (Request $pedido) => $pedido->hasHeader('If-None-Match', '"v1"'));
    }

    public function test_sem_confirmar_a_leitura_a_proxima_busca_traz_tudo_de_novo(): void
    {
        Http::fake([self::URL => Http::response($this->issues(), 200, ['ETag' => '"v1"'])]);

        $fonte = new RepositorioDeVagasDoGithub('exemplo/vagas');
        $fonte->buscar();
        $fonte->buscar();

        Http::assertNotSent(fn (Request $pedido) => $pedido->hasHeader('If-None-Match'));
    }

    public function test_usa_o_token_quando_configurado(): void
    {
        Http::fake([self::URL => Http::response([])]);

        (new RepositorioDeVagasDoGithub('exemplo/vagas', 'token-de-teste'))->buscar();

        Http::assertSent(fn (Request $pedido) => $pedido->hasHeader('Authorization', 'Bearer token-de-teste'));
    }

    public function test_erro_da_api_vira_excecao(): void
    {
        Http::fake([self::URL => Http::response(['message' => 'API rate limit exceeded'], 403)]);

        $this->assertThrows(fn () => (new RepositorioDeVagasDoGithub('exemplo/vagas'))->buscar(), RequestException::class);
    }
}
