<?php

namespace Tests\Feature;

use App\Vagas\Fontes\Remotive;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RemotiveTest extends TestCase
{
    public function test_converte_as_vagas_com_regiao_e_sem_os_rotulos(): void
    {
        Http::fake(['remotive.com/*' => Http::response(['jobs' => [[
            'id' => 99,
            'url' => 'https://remotive.com/remote-jobs/software-dev/dev-php-99',
            'title' => 'Senior PHP Developer',
            'company_name' => 'Empresa Fictícia',
            'tags' => ['php', 'laravel', 'marketing'],
            'publication_date' => '2026-11-08T21:47:58',
            'candidate_required_location' => 'USA Only',
        ]]])]);

        [$vaga] = (new Remotive('software-dev'))->buscar();

        $this->assertSame('remotive', $vaga->fonte);
        $this->assertSame('99', $vaga->idExterno);
        $this->assertTrue($vaga->remota);
        $this->assertSame('USA Only', $vaga->regiao);
        // Rótulos do Remotive são amplos demais para o filtro.
        $this->assertSame([], $vaga->etiquetas);
        $this->assertSame('2026-11-08T21:47:58+00:00', $vaga->publicadaEm->toIso8601String());
        // O aviso tem de levar de volta ao Remotive (termos de uso).
        $this->assertStringStartsWith('https://remotive.com/', $vaga->url);
    }

    public function test_um_pedido_so_pela_categoria(): void
    {
        Http::fake(['remotive.com/*' => Http::response(['jobs' => []])]);

        (new Remotive('software-dev'))->buscar();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $pedido) => $pedido['category'] === 'software-dev');
    }

    public function test_respeita_as_quatro_leituras_por_dia(): void
    {
        $this->assertSame(24 * 60 / 4, (new Remotive('software-dev'))->intervaloMinimoEmMinutos());
    }
}
