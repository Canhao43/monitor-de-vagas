<?php

namespace App\Avisos;

use App\Models\Vaga;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Manda para o Telegram as vagas que ainda não foram avisadas.
 *
 * Pelo menos uma vez: a vaga só é marcada como avisada depois que o Telegram
 * confirma o envio. Se o programa cair entre o envio e a marcação, a vaga é
 * avisada de novo na próxima execução. O contrário, marcar antes de enviar,
 * perderia a vaga numa queda. Melhor repetir um aviso do que perder uma vaga.
 */
final class Avisador
{
    private const VAGAS_POR_MENSAGEM = 10;

    // O Telegram aceita até 4.096 caracteres por mensagem.
    private const TAMANHO_MAXIMO = 3900;

    public function __construct(
        private readonly Telegram $telegram,
        private readonly string $chatId,
    ) {}

    public function avisarPendentes(): ResultadoDoAviso
    {
        $resultado = new ResultadoDoAviso;
        $pendentes = Vaga::whereNull('avisada_em')->orderBy('id')->get();

        foreach ($this->lotes($pendentes) as $lote) {
            try {
                $this->telegram->enviar($this->chatId, $this->mensagem($lote, $pendentes->count()));
            } catch (Throwable $erro) {
                // Para no primeiro erro: o que sobrou continua pendente e vai
                // na próxima execução. Isso vale também para o 429.
                $resultado->erro = $erro->getMessage();
                Log::warning('Aviso no Telegram falhou', ['erro' => $erro->getMessage()]);

                break;
            }

            Vaga::whereKey($lote->pluck('id'))->update(['avisada_em' => now()]);
            $resultado->avisadas += $lote->count();
        }

        return $resultado;
    }

    /**
     * Junta as vagas em mensagens de até 10, sem passar do limite do Telegram.
     *
     * @param  Collection<int, Vaga>  $vagas
     * @return list<Collection<int, Vaga>>
     */
    private function lotes(Collection $vagas): array
    {
        $lotes = [];
        $atual = collect();
        $tamanho = 0;

        foreach ($vagas as $vaga) {
            $bloco = mb_strlen($this->bloco($vaga));

            if ($atual->count() === self::VAGAS_POR_MENSAGEM || ($atual->isNotEmpty() && $tamanho + $bloco > self::TAMANHO_MAXIMO)) {
                $lotes[] = $atual;
                $atual = collect();
                $tamanho = 0;
            }

            $atual->push($vaga);
            $tamanho += $bloco;
        }

        if ($atual->isNotEmpty()) {
            $lotes[] = $atual;
        }

        return $lotes;
    }

    /**
     * @param  Collection<int, Vaga>  $lote
     */
    private function mensagem(Collection $lote, int $total): string
    {
        $cabecalho = $total === 1 ? '🔔 <b>1 vaga nova</b>' : "🔔 <b>{$total} vagas novas</b>";

        return $cabecalho."\n\n".$lote->map($this->bloco(...))->implode("\n\n");
    }

    private function bloco(Vaga $vaga): string
    {
        $detalhes = array_filter([
            $vaga->empresa ? $this->escapar($vaga->empresa) : null,
            // Os termos do Remotive pedem que a vaga diga de onde veio; vale para todas.
            $this->nomeDaFonte($vaga->fonte),
            $vaga->remota ? 'remota' : null,
        ]);

        return '<b>'.$this->escapar($vaga->titulo)."</b>\n".implode(' · ', $detalhes)."\n".$this->escapar($vaga->url);
    }

    private function nomeDaFonte(string $fonte): string
    {
        return match (true) {
            $fonte === 'gupy' => 'Gupy',
            $fonte === 'remotive' => 'Remotive',
            str_starts_with($fonte, 'github:') => substr($fonte, 7).' no GitHub',
            default => $fonte,
        };
    }

    private function escapar(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
