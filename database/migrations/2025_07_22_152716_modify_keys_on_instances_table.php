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
        Schema::table('instances', function (Blueprint $table) {
            // 1. Remove a coluna antiga, se ela existir.
            if (Schema::hasColumn('instances', 'openai_api_key')) {
                $table->dropColumn('openai_api_key');
            }

            // 2. Adiciona a nova coluna de chave estrangeira.
            // Ela pode ser nula e, se a credencial for deletada, o valor aqui se tornará nulo.
            $table->foreignId('credential_id')
                  ->nullable()
                  ->after('user_id') // Posiciona a coluna para melhor organização
                  ->constrained('credentials')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('instances', function (Blueprint $table) {
            // O processo reverso: remove a nova coluna e adiciona a antiga
            $table->dropForeign(['credential_id']);
            $table->dropColumn('credential_id');

            $table->text('openai_api_key')->nullable()->after('user_id');
        });
    }
};