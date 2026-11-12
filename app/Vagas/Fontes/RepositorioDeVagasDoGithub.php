<?php

namespace App\Vagas\Fontes;

use App\Vagas\VagaEncontrada;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Repositórios como backend-br/vagas e phpdevbr/vagas, em que cada vaga é uma
 * issue aberta. Lidos pela API oficial do GitHub.
 */
final class RepositorioDeVagasDoGithub implements Fonte
{
    private ?string $etagPendente = null;

    public function __construct(
        private readonly string $repositorio,
        private readonly ?string $token = null,
    ) {}

    public function nome(): string
    {
        return "github:{$this->repositorio}";
    }

    /**
     * Sem intervalo: a requisição condicional faz a leitura sem mudança sair
     * barata, e a cota anônima (60 por hora) sobra para duas por execução.
     */
    public function intervaloMinimoEmMinutos(): int
    {
        return 0;
    }

    public function buscar(): array
    {
        $cabecalhos = [
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];

        // Requisição condicional: se nada mudou desde a última leitura, o
        // GitHub responde 304 sem corpo, e não há vaga nova para ver.
        if ($etag = Cache::get($this->chaveDoEtag())) {
            $cabecalhos['If-None-Match'] = $etag;
        }

        $pedido = Http::withHeaders($cabecalhos)->withUserAgent('monitor-de-vagas')->timeout(15);
        if ($this->token) {
            $pedido = $pedido->withToken($this->token);
        }

        $resposta = $pedido->get("https://api.github.com/repos/{$this->repositorio}/issues", [
            'state' => 'open',
            'sort' => 'created',
            'direction' => 'desc',
            'per_page' => 100,
        ]);

        if ($resposta->status() === 304) {
            return [];
        }

        $resposta->throw();
        $this->etagPendente = $resposta->header('ETag') ?: null;

        $vagas = [];
        foreach ($resposta->json() as $issue) {
            // A API de issues devolve pull requests junto.
            if (isset($issue['pull_request'])) {
                continue;
            }

            $etiquetas = array_column($issue['labels'] ?? [], 'name');

            $vagas[] = new VagaEncontrada(
                fonte: $this->nome(),
                idExterno: (string) $issue['number'],
                titulo: $issue['title'],
                url: $issue['html_url'],
                remota: $this->remota($issue['title'], $etiquetas),
                publicadaEm: CarbonImmutable::parse($issue['created_at']),
                etiquetas: $etiquetas,
            );
        }

        return $vagas;
    }

    /**
     * O ETag só vale depois que as vagas foram gravadas. Guardado antes, uma
     * falha na gravação faria a próxima busca receber 304 e perder as vagas.
     */
    public function confirmarLeitura(): void
    {
        if ($this->etagPendente !== null) {
            Cache::forever($this->chaveDoEtag(), $this->etagPendente);
            $this->etagPendente = null;
        }
    }

    /**
     * @param  list<string>  $etiquetas
     */
    private function remota(string $titulo, array $etiquetas): ?bool
    {
        $texto = Str::lower(Str::ascii($titulo.' '.implode(' ', $etiquetas)));

        if (preg_match('/\bremot[oa]\b|\bremote\b|home ?office/', $texto)) {
            return true;
        }

        return preg_match('/\bpresencial\b|\bhibrid[oa]\b/', $texto) ? false : null;
    }

    private function chaveDoEtag(): string
    {
        return 'vagas:etag:'.$this->nome();
    }
}
