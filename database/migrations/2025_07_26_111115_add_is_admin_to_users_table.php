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
        Schema::table('users', function (Blueprint $table) {
            // Adiciona a nova coluna 'is_admin'
            $table->boolean('is_admin')
                  ->default(false) // Define o valor padrão como 'false'
                  ->after('remember_token'); // Posiciona a coluna depois do 'remember_token'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove a coluna se a migration for revertida
            $table->dropColumn('is_admin');
        });
    }
};