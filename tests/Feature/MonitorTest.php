<?php

namespace Tests\Feature;

use App\Models\Vaga;
use App\Vagas\Filtro;
use App\Vagas\Fontes\Fonte;
use App\Vagas\Monitor;
use App\Vagas\VagaEncontrada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class MonitorTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Fonte de mentira: devolve as vagas dadas, ou falha, e conta as confirmações.
     *
     * @param  list<VagaEncontrada>  $vagas
     */
    private function fonte(string $nome, array $vagas = [], bool $falha = false): Fonte
    {
        return new class($nome, $vagas, $falha) implements Fonte
        {
            public int $confirmacoes = 0;

            public function __construct(private string $nome, private array $vagas, private bool $falha) {}

            public function nome(): string
            {
                return $this->nome;
            }

            public function buscar(): array
            {
                if ($this->falha) {
                    throw new RuntimeException('fonte fora do ar');
                }

                return $this->vagas;
            }

            public function confirmarLeitura(): void
            {
                $this->confirmacoes++;
            }
        };
    }

    private function vaga(string $fonte, string $id, string $titulo = 'Desenvolvedor PHP', ?bool $remota = true): VagaEncontrada
    {
        return new VagaEncontrada($fonte, $id, $titulo, "https://exemplo.test/{$id}", $remota);
    }

    private function monitor(Fonte ...$fontes): Monitor
    {
        return new Monitor($fontes, new Filtro(['php'], ['junior'], true));
    }

    public function test_grava_so_o_que_bate_com_o_perfil(): void
    {
        $fonte = $this->fonte('a', [
            $this->vaga('a', '1'),
            $this->vaga('a', '2', 'Desenvolvedor Java'),
            $this->vaga('a', '3', remota: false),
        ]);

        $resultado = $this->monitor($fonte)->verificar();

        $this->assertSame(['1'], array_map(fn ($v) => $v->idExterno, $resultado->novas));
        $this->assertSame(1, Vaga::count());
        $this->assertSame(1, $fonte->confirmacoes);
    }

    public function test_a_mesma_vaga_na_execucao_seguinte_nao_e_nova(): void
    {
        $fonte = $this->fonte('a', [$this->vaga('a', '1')]);

        $this->assertCount(1, $this->monitor($fonte)->verificar()->novas);
        $this->assertCount(0, $this->monitor($fonte)->verificar()->novas);
        $this->assertSame(1, Vaga::count());
    }

    public function test_o_mesmo_id_em_fontes_diferentes_sao_vagas_diferentes(): void
    {
        $resultado = $this->monitor($this->fonte('a', [$this->vaga('a', '1')]), $this->fonte('b', [$this->vaga('b', '1')]))->verificar();

        $this->assertCount(2, $resultado->novas);
    }

    public function test_vaga_gravada_por_outra_execucao_ao_mesmo_tempo_nao_conta_como_nova(): void
    {
        // Outra execução gravou primeiro: a chave única barra a segunda.
        Vaga::create(['fonte' => 'a', 'id_externo' => '1', 'titulo' => 'x', 'url' => 'https://exemplo.test/1', 'vista_em' => now()]);

        $resultado = $this->monitor($this->fonte('a', [$this->vaga('a', '1')]))->verificar();

        $this->assertCount(0, $resultado->novas);
        // E não como falha da fonte: vaga repetida é o caso normal.
        $this->assertSame([], $resultado->falhas);
    }

    public function test_simular_nao_grava_nem_confirma_a_leitura(): void
    {
        $fonte = $this->fonte('a', [$this->vaga('a', '1')]);

        $resultado = $this->monitor($fonte)->verificar(simular: true);

        $this->assertCount(1, $resultado->novas);
        $this->assertSame(0, Vaga::count());
        $this->assertSame(0, $fonte->confirmacoes);
    }

    public function test_uma_fonte_fora_do_ar_nao_impede_as_outras(): void
    {
        $quebrada = $this->fonte('quebrada', falha: true);
        $boa = $this->fonte('boa', [$this->vaga('boa', '1')]);

        $resultado = $this->monitor($quebrada, $boa)->verificar();

        $this->assertSame(['quebrada' => 'fonte fora do ar'], $resultado->falhas);
        $this->assertCount(1, $resultado->novas);
        $this->assertFalse($resultado->todasFalharam());
        $this->assertSame(0, $quebrada->confirmacoes);
    }
}
