<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Adiciona o campo de data de vencimento.
            // O tipo 'timestamp' é ideal, pois é fácil de manipular e consultar.
            // Pode ser nulo caso alguns tipos de pagamento não tenham vencimento.
            $table->timestamp('expires_at')->nullable()->after('payload');

            // Adiciona o campo booleano para "credencial padrão" (ou "crédito ativo").
            // 'default(true)' garante que novos registros sejam marcados como true.
            $table->boolean('is_default_credit')->default(true)->after('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Remove as colunas se a migration for revertida.
            $table->dropColumn(['expires_at', 'is_default_credit']);
        });
    }
};