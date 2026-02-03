<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sequence_chats', function (Blueprint $table) {
            if (!Schema::hasColumn('sequence_chats', 'cliente_lead_id')) {
                $table->foreignId('cliente_lead_id')
                    ->nullable()
                    ->after('chat_id')
                    ->constrained('cliente_lead')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sequence_chats', function (Blueprint $table) {
            if (Schema::hasColumn('sequence_chats', 'cliente_lead_id')) {
                $table->dropForeign(['cliente_lead_id']);
                $table->dropColumn('cliente_lead_id');
            }
        });
    }
};
