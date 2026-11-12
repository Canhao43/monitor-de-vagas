<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vagas', function (Blueprint $table) {
            $table->id();
            $table->string('fonte', 60);
            $table->string('id_externo', 100);
            $table->string('titulo', 300);
            $table->string('empresa', 200)->nullable();
            // Nulo quando a fonte não diz.
            $table->boolean('remota')->nullable();
            $table->string('url', 500);
            $table->timestamp('publicada_em')->nullable();
            $table->timestamp('vista_em');

            // A mesma vaga, lida de novo na próxima execução, não é nova: é a
            // chave única que decide isso, e não uma consulta antes de gravar.
            $table->unique(['fonte', 'id_externo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vagas');
    }
};
