<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('user_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('conexao_id')->nullable()->after('cliente_id')->constrained('conexoes')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sequences', function (Blueprint $table) {
            $table->dropForeign(['conexao_id']);
            $table->dropForeign(['cliente_id']);
            $table->dropColumn(['conexao_id', 'cliente_id']);
        });
    }
};
