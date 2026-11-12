<?php

namespace App\Vagas;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * O perfil de quem procura: termos que interessam, termos que tiram a vaga
 * e se só vale remota. Olha o título e os rótulos da fonte.
 */
final readonly class Filtro
{
    /**
     * @param  list<string>  $termos
     * @param  list<string>  $excluir
     * @param  list<string>  $regioesAceitas  vale só para vaga que restringe região
     */
    public function __construct(
        private array $termos,
        private array $excluir,
        private bool $somenteRemotas,
        private array $regioesAceitas = [],
        private ?CarbonImmutable $publicadaDesde = null,
    ) {}

    public function aceita(VagaEncontrada $vaga): bool
    {
        // Vaga sem data passa: a idade só tira a vaga quando a fonte a informa.
        if ($this->publicadaDesde !== null && $vaga->publicadaEm?->lt($this->publicadaDesde)) {
            return false;
        }

        // Remota desconhecida (null) não passa quando o perfil exige remota:
        // melhor perder uma vaga ambígua do que avisar uma presencial.
        if ($this->somenteRemotas && $vaga->remota !== true) {
            return false;
        }

        // Remota, mas só para quem mora nos EUA, por exemplo: é ruído.
        if ($vaga->regiao !== null && ! $this->contemAlgum($this->normalizar($vaga->regiao), $this->regioesAceitas)) {
            return false;
        }

        $texto = $this->normalizar($vaga->titulo.' '.implode(' ', $vaga->etiquetas));

        return $this->contemAlgum($texto, $this->termos) && ! $this->contemAlgum($texto, $this->excluir);
    }

    /**
     * @param  list<string>  $termos
     */
    private function contemAlgum(string $texto, array $termos): bool
    {
        foreach ($termos as $termo) {
            // Palavra inteira: "php" não casa com "phpunit", e "jr" não casa
            // com o meio de outra palavra.
            if (preg_match('/(?<![a-z0-9])'.preg_quote($this->normalizar($termo), '/').'(?![a-z0-9])/', $texto)) {
                return true;
            }
        }

        return false;
    }

    private function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii($texto));
    }
}
