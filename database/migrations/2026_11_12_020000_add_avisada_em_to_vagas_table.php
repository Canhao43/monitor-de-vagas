<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vagas', function (Blueprint $table) {
            // Nula até o Telegram confirmar o envio. Vaga com aviso pendente
            // é tentada de novo na próxima execução.
            $table->timestamp('avisada_em')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('vagas', function (Blueprint $table) {
            $table->dropIndex(['avisada_em']);
            $table->dropColumn('avisada_em');
        });
    }
};
