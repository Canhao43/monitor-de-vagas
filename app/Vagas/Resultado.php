<?php

namespace App\Vagas;

final class Resultado
{
    /** @var list<VagaEncontrada> */
    public array $novas = [];

    /** @var array<string, string> nome da fonte => motivo */
    public array $falhas = [];

    /** @var list<string> fontes lidas há menos tempo que o intervalo delas */
    public array $puladas = [];

    public int $fontesConsultadas = 0;

    public function todasFalharam(): bool
    {
        return $this->fontesConsultadas > 0 && count($this->falhas) === $this->fontesConsultadas;
    }
}
