<?php

namespace Tests\Unit;

use App\Vagas\Filtro;
use App\Vagas\VagaEncontrada;
use PHPUnit\Framework\TestCase;

class FiltroTest extends TestCase
{
    private function vaga(string $titulo, ?bool $remota = true, array $etiquetas = []): VagaEncontrada
    {
        return new VagaEncontrada('teste', '1', $titulo, 'https://exemplo.test/1', $remota, etiquetas: $etiquetas);
    }

    private function filtro(bool $somenteRemotas = true): Filtro
    {
        return new Filtro(['php', 'laravel', 'back-end', 'protheus'], ['estagio', 'junior', 'jr'], $somenteRemotas);
    }

    public function test_aceita_vaga_com_algum_termo(): void
    {
        $this->assertTrue($this->filtro()->aceita($this->vaga('Desenvolvedor PHP Pleno')));
        $this->assertTrue($this->filtro()->aceita($this->vaga('Analista de integração TOTVS Protheus')));
        $this->assertFalse($this->filtro()->aceita($this->vaga('Desenvolvedor Java Pleno')));
    }

    public function test_ignora_maiuscula_e_acento(): void
    {
        $this->assertTrue($this->filtro()->aceita($this->vaga('DESENVOLVEDOR BACK-END')));
        $this->assertFalse($this->filtro()->aceita($this->vaga('Desenvolvedor PHP Estágio')));
    }

    public function test_compara_palavra_inteira(): void
    {
        $this->assertFalse($this->filtro()->aceita($this->vaga('Especialista em PHPUnit')));
        // "jr" excluído não pode tirar uma vaga só por aparecer dentro de outra palavra.
        $this->assertTrue($this->filtro()->aceita($this->vaga('Dev PHP - Jrsoft Sistemas')));
        $this->assertFalse($this->filtro()->aceita($this->vaga('Dev PHP Jr')));
    }

    public function test_exclui_pelo_rotulo_da_fonte(): void
    {
        $this->assertFalse($this->filtro()->aceita($this->vaga('Desenvolvedor Back-end', etiquetas: ['Júnior', 'Remoto'])));
        $this->assertTrue($this->filtro()->aceita($this->vaga('Desenvolvedor de sistemas', etiquetas: ['PHP'])));
    }

    public function test_so_remota_recusa_presencial_e_desconhecida(): void
    {
        $this->assertFalse($this->filtro()->aceita($this->vaga('Dev PHP', remota: false)));
        $this->assertFalse($this->filtro()->aceita($this->vaga('Dev PHP', remota: null)));

        $this->assertTrue($this->filtro(somenteRemotas: false)->aceita($this->vaga('Dev PHP', remota: null)));
    }
}
