<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            // Limite de agendamentos simultâneos por horário (capacidade)
            $table->unsignedInteger('limite_por_horario')
                  ->default(1)
                  ->after('slug'); // ajuste a coluna de referência se quiser
        });
    }

    public function down(): void
    {
        Schema::table('agendas', function (Blueprint $table) {
            $table->dropColumn('limite_por_horario');
        });
    }
};
