<?php

namespace App\Vagas;

use App\Models\Vaga;
use App\Vagas\Fontes\Fonte;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class Monitor
{
    /**
     * @param  list<Fonte>  $fontes
     */
    public function __construct(
        private readonly array $fontes,
        private readonly Filtro $filtro,
    ) {}

    /**
     * Lê cada fonte, filtra pelo perfil e grava o que nunca foi visto. Uma
     * fonte que falha fica registrada no resultado, e as outras seguem.
     *
     * Simulando, nada é gravado e nenhuma fonte guarda o ponto de leitura:
     * a próxima execução de verdade vê exatamente o mesmo.
     */
    public function verificar(bool $simular = false): Resultado
    {
        $resultado = new Resultado;

        foreach ($this->fontes as $fonte) {
            // Fonte lida há menos tempo que o intervalo dela fica para a
            // próxima execução, inclusive na simulação: uma leitura simulada
            // também conta para o limite da fonte.
            if (! $this->podeLer($fonte)) {
                $resultado->puladas[] = $fonte->nome();

                continue;
            }

            $resultado->fontesConsultadas++;

            try {
                $encontradas = array_filter($fonte->buscar(), $this->filtro->aceita(...));

                foreach ($encontradas as $vaga) {
                    if ($simular ? $this->jaVista($vaga) === false : $this->gravarSeNova($vaga)) {
                        $resultado->novas[] = $vaga;
                    }
                }

                if (! $simular) {
                    $fonte->confirmarLeitura();
                    // Só a leitura que deu certo marca o relógio: a que falhou
                    // é tentada de novo na próxima execução.
                    Cache::forever($this->chaveDaUltimaLeitura($fonte), now()->toIso8601String());
                }
            } catch (Throwable $erro) {
                $resultado->falhas[$fonte->nome()] = $erro->getMessage();
                Log::warning('Fonte de vagas falhou', ['fonte' => $fonte->nome(), 'erro' => $erro->getMessage()]);
            }
        }

        return $resultado;
    }

    private function podeLer(Fonte $fonte): bool
    {
        $ultima = Cache::get($this->chaveDaUltimaLeitura($fonte));

        return $ultima === null
            || CarbonImmutable::parse($ultima)->addMinutes($fonte->intervaloMinimoEmMinutos())->lte(now());
    }

    private function chaveDaUltimaLeitura(Fonte $fonte): string
    {
        return 'vagas:ultima-leitura:'.$fonte->nome();
    }

    private function jaVista(VagaEncontrada $vaga): bool
    {
        return Vaga::where('fonte', $vaga->fonte)->where('id_externo', $vaga->idExterno)->exists();
    }

    /**
     * Quem decide se a vaga é nova é a chave única do banco: duas execuções
     * ao mesmo tempo não gravam (nem avisam) a mesma vaga duas vezes.
     */
    private function gravarSeNova(VagaEncontrada $vaga): bool
    {
        try {
            Vaga::create([
                'fonte' => $vaga->fonte,
                'id_externo' => $vaga->idExterno,
                'titulo' => mb_substr($vaga->titulo, 0, 300),
                'empresa' => $vaga->empresa,
                'remota' => $vaga->remota,
                'url' => $vaga->url,
                'publicada_em' => $vaga->publicadaEm,
                'vista_em' => now(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }
}
