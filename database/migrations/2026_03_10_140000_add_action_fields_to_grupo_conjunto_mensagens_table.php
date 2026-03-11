<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupo_conjunto_mensagens', function (Blueprint $table): void {
            $table->string('action_type', 40)
                ->default('send_text')
                ->after('mensagem');
            $table->json('payload')->nullable()->after('action_type');

            $table->index(['action_type', 'status'], 'gcm_action_type_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('grupo_conjunto_mensagens', function (Blueprint $table): void {
            $table->dropIndex('gcm_action_type_status_idx');
            $table->dropColumn(['action_type', 'payload']);
        });
    }
};
