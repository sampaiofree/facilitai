<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->foreignId('agenda_id')
                ->nullable()
                ->constrained('agendas')
                ->nullOnDelete(); // se a agenda for excluída, zera o campo
        });
    }

    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            $table->dropForeign(['agenda_id']);
            $table->dropColumn('agenda_id');
        });
    }
};
