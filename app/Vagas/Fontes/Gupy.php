<?php

namespace App\Vagas\Fontes;

use App\Vagas\VagaEncontrada;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * Portal de vagas da Gupy, o ATS mais usado no Brasil. É o endereço que o
 * próprio portal usa, e não uma API documentada: pode mudar sem aviso, e por
 * isso o CI lê a fonte de verdade. Sem disfarce de navegador: se a Gupy
 * passar a bloquear robô, a fonte fica de fora.
 */
final class Gupy implements Fonte
{
    private const ENDERECO = 'https://portal.gupy.io/api/job-search/jobs';

    /**
     * @param  list<string>  $buscas  a busca da Gupy é pelo título e estreita: um termo por pedido
     */
    public function __construct(private readonly array $buscas) {}

    public function nome(): string
    {
        return 'gupy';
    }

    public function intervaloMinimoEmMinutos(): int
    {
        return 60;
    }

    public function buscar(): array
    {
        $vagas = [];

        foreach ($this->buscas as $termo) {
            $resposta = Http::withUserAgent('monitor-de-vagas')
                ->acceptJson()
                ->timeout(15)
                ->get(self::ENDERECO, ['jobName' => $termo, 'limit' => 100, 'offset' => 0])
                ->throw();

            foreach ($resposta->json('data', []) as $vaga) {
                // A mesma vaga aparece em mais de uma busca ("php" e "laravel").
                $vagas[(string) $vaga['id']] ??= new VagaEncontrada(
                    fonte: $this->nome(),
                    idExterno: (string) $vaga['id'],
                    titulo: $vaga['name'],
                    url: $vaga['jobUrl'],
                    remota: match ($vaga['workplaceType'] ?? null) {
                        'remote' => true,
                        'hybrid', 'on-site' => false,
                        default => null,
                    },
                    empresa: $vaga['careerPageName'] ?? null,
                    publicadaEm: isset($vaga['publishedDate']) ? CarbonImmutable::parse($vaga['publishedDate']) : null,
                );
            }
        }

        return array_values($vagas);
    }

    public function confirmarLeitura(): void {}
}
