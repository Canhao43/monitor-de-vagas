<?php

namespace App\Vagas\Fontes;

use App\Vagas\VagaEncontrada;

interface Fonte
{
    public function nome(): string;

    /**
     * As vagas abertas na fonte agora. Lança exceção se a fonte falhar: quem
     * chama decide o que fazer, e uma fonte fora do ar não derruba as outras.
     *
     * @return list<VagaEncontrada>
     */
    public function buscar(): array;

    /**
     * Chamado depois que as vagas da última busca foram gravadas. É aqui que
     * a fonte guarda o que precisa para a próxima busca ser incremental.
     */
    public function confirmarLeitura(): void;
}
