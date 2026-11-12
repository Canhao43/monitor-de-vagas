<?php

namespace App\Console\Commands;

use App\Vagas\Monitor;
use App\Vagas\VagaEncontrada;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('vagas:verificar {--simular : Mostra o que seria novo, sem gravar nada}')]
#[Description('Lê as fontes de vaga e registra as novas que batem com o perfil')]
class VerificarVagas extends Command
{
    public function handle(Monitor $monitor): int
    {
        $simular = (bool) $this->option('simular');
        $resultado = $monitor->verificar($simular);

        $this->info(sprintf('%s: %d vaga(s) nova(s).', $simular ? 'Simulação' : 'Verificação', count($resultado->novas)));

        if ($resultado->novas !== []) {
            $this->table(['Fonte', 'Vaga', 'Remota', 'Link'], array_map(fn (VagaEncontrada $v) => [
                $v->fonte,
                mb_strimwidth($v->titulo, 0, 70, '…'),
                match ($v->remota) {
                    true => 'sim', false => 'não', null => '?'
                },
                $v->url,
            ], $resultado->novas));
        }

        foreach ($resultado->falhas as $fonte => $motivo) {
            $this->warn("A fonte {$fonte} falhou: {$motivo}");
        }

        if ($resultado->puladas !== []) {
            $this->line('Lidas há pouco, ficam para a próxima: '.implode(', ', $resultado->puladas).'.');
        }

        // Falha de fonte não é falha do comando: as outras fontes rodaram, e
        // a próxima execução tenta de novo. Só falha se todas falharem.
        return $resultado->todasFalharam() ? self::FAILURE : self::SUCCESS;
    }
}
