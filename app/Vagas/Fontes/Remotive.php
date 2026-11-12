<?php

namespace App\Vagas\Fontes;

use App\Vagas\VagaEncontrada;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

/**
 * API pública do Remotive, só de vagas remotas e internacionais. Os termos
 * de uso pedem no máximo 4 leituras por dia e que o aviso leve de volta ao
 * link do Remotive e diga que a vaga veio de lá.
 */
final class Remotive implements Fonte
{
    private const ENDERECO = 'https://remotive.com/api/remote-jobs';

    public function __construct(private readonly string $categoria) {}

    public function nome(): string
    {
        return 'remotive';
    }

    /**
     * 6 horas: as 4 leituras por dia que os termos de uso permitem.
     */
    public function intervaloMinimoEmMinutos(): int
    {
        return 6 * 60;
    }

    /**
     * Um pedido só, pela categoria inteira, e o filtro do perfil faz o resto.
     * Um pedido por termo multiplicaria as leituras que os termos limitam.
     */
    public function buscar(): array
    {
        $resposta = Http::withUserAgent('monitor-de-vagas')
            ->acceptJson()
            ->timeout(20)
            ->get(self::ENDERECO, ['category' => $this->categoria])
            ->throw();

        return array_map(fn (array $vaga) => new VagaEncontrada(
            fonte: $this->nome(),
            idExterno: (string) $vaga['id'],
            titulo: $vaga['title'],
            url: $vaga['url'],
            remota: true,
            empresa: $vaga['company_name'] ?? null,
            // Sem fuso na API; o horário é tratado como UTC.
            publicadaEm: isset($vaga['publication_date']) ? CarbonImmutable::parse($vaga['publication_date'], 'UTC') : null,
            // Os rótulos do Remotive não entram no filtro: são amplos demais (uma
            // vaga de assistente de escritório vem com "php"). Vale só o título.
            etiquetas: [],
            regiao: ($vaga['candidate_required_location'] ?? '') ?: null,
        ), $resposta->json('jobs', []));
    }

    public function confirmarLeitura(): void {}
}
