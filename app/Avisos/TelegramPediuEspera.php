<?php

namespace App\Avisos;

use RuntimeException;

/**
 * O Telegram limita a frequência de mensagens e responde 429 dizendo quantos
 * segundos esperar. O monitor não espera: para e deixa o resto para a
 * próxima execução, uma hora depois.
 */
final class TelegramPediuEspera extends RuntimeException
{
    public function __construct(public readonly int $segundos)
    {
        parent::__construct("O Telegram pediu para esperar {$segundos} s.");
    }
}
