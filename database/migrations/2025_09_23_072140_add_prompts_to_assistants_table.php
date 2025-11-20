<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('assistants', function (Blueprint $table) {
        // Adiciona as novas colunas após a coluna 'instructions'
        $table->text('systemPrompt')->nullable()->after('instructions');
        $table->text('developerPrompt')->nullable()->after('systemPrompt');
    });
}

public function down(): void
{
    Schema::table('assistants', function (Blueprint $table) {
        // Remove as colunas se a migration for revertida
        $table->dropColumn(['systemPrompt', 'developerPrompt']);
    });
}
};
