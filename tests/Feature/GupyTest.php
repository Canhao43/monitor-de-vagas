<?php

namespace Tests\Feature;

use App\Vagas\Fontes\Gupy;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GupyTest extends TestCase
{
    /**
     * Formato real do portal da Gupy, com dados fictícios.
     */
    private function vaga(int $id, string $nome, string $modelo): array
    {
        return [
            'id' => $id,
            'name' => $nome,
            'careerPageName' => 'Empresa Fictícia',
            'workplaceType' => $modelo,
            'publishedDate' => '2026-11-06T00:58:03.410Z',
            'jobUrl' => "https://ficticia.gupy.io/job/{$id}",
        ];
    }

    public function test_converte_as_vagas_e_le_o_modelo_de_trabalho(): void
    {
        Http::fake(['portal.gupy.io/*' => Http::response(['data' => [
            $this->vaga(1, 'Desenvolvedor PHP', 'remote'),
            $this->vaga(2, 'Desenvolvedor PHP Híbrido', 'hybrid'),
            $this->vaga(3, 'Desenvolvedor PHP Presencial', 'on-site'),
            $this->vaga(4, 'Desenvolvedor PHP', 'outro'),
        ]])]);

        $vagas = (new Gupy(['php']))->buscar();

        $this->assertSame([true, false, false, null], array_map(fn ($v) => $v->remota, $vagas));
        $this->assertSame('Empresa Fictícia', $vagas[0]->empresa);
        $this->assertSame('https://ficticia.gupy.io/job/1', $vagas[0]->url);
        $this->assertSame('2026-11-06', $vagas[0]->publicadaEm->toDateString());
    }

    public function test_um_pedido_por_termo_e_a_mesma_vaga_vem_uma_vez(): void
    {
        Http::fake(['portal.gupy.io/*' => Http::sequence()
            ->push(['data' => [$this->vaga(1, 'Dev PHP/Laravel', 'remote')]])
            ->push(['data' => [$this->vaga(1, 'Dev PHP/Laravel', 'remote'), $this->vaga(2, 'Dev Laravel', 'remote')]])]);

        $vagas = (new Gupy(['php', 'laravel']))->buscar();

        $this->assertSame(['1', '2'], array_map(fn ($v) => $v->idExterno, $vagas));
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $pedido) => $pedido['jobName'] === 'laravel');
    }

    public function test_nao_se_disfarca_de_navegador(): void
    {
        Http::fake(['portal.gupy.io/*' => Http::response(['data' => []])]);

        (new Gupy(['php']))->buscar();

        Http::assertSent(fn (Request $pedido) => $pedido->header('User-Agent') === ['monitor-de-vagas']);
    }

    public function test_erro_do_portal_vira_excecao(): void
    {
        Http::fake(['portal.gupy.io/*' => Http::response('', 403)]);

        $this->assertThrows(fn () => (new Gupy(['php']))->buscar(), RequestException::class);
    }
}
