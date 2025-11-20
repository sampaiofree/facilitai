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
    Schema::table('chats', function (Blueprint $table) {
        // Adiciona a nova coluna 'conv_id', que pode ser nula, após a coluna 'thread_id'
        $table->string('conv_id')->nullable()->after('thread_id')->comment('ID da conversa no sistema de origem (Evolution)');
    });
}

public function down(): void
{
    Schema::table('chats', function (Blueprint $table) {
        // Remove a coluna se a migration for revertida
        $table->dropColumn('conv_id');
    });
}
};
