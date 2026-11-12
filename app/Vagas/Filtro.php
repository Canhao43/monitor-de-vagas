<?php

namespace App\Vagas;

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
     */
    public function __construct(
        private array $termos,
        private array $excluir,
        private bool $somenteRemotas,
    ) {}

    public function aceita(VagaEncontrada $vaga): bool
    {
        // Remota desconhecida (null) não passa quando o perfil exige remota:
        // melhor perder uma vaga ambígua do que avisar uma presencial.
        if ($this->somenteRemotas && $vaga->remota !== true) {
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
