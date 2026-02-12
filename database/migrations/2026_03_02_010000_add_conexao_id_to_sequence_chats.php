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
        if (!Schema::hasTable('sequence_chats')) {
            return;
        }

        Schema::table('sequence_chats', function (Blueprint $table) {
            if (!Schema::hasColumn('sequence_chats', 'conexao_id')) {
                $table->foreignId('conexao_id')
                    ->nullable()
                    ->constrained('conexoes')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('sequence_chats') || !Schema::hasColumn('sequence_chats', 'conexao_id')) {
            return;
        }

        Schema::table('sequence_chats', function (Blueprint $table) {
            $table->dropForeign(['conexao_id']);
            $table->dropColumn('conexao_id');
        });
    }
};

