<?php

namespace App\Vagas;

use Carbon\CarbonImmutable;

/**
 * Uma vaga como a fonte a entregou, já no formato comum a todas as fontes.
 */
final readonly class VagaEncontrada
{
    /**
     * @param  list<string>  $etiquetas  rótulos da fonte (nível, stack, modelo de trabalho), quando houver
     */
    public function __construct(
        public string $fonte,
        public string $idExterno,
        public string $titulo,
        public string $url,
        public ?bool $remota = null,
        public ?string $empresa = null,
        public ?CarbonImmutable $publicadaEm = null,
        public array $etiquetas = [],
    ) {}
}
