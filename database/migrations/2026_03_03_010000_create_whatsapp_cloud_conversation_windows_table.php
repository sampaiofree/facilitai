<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_cloud_conversation_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_lead_id')
                ->constrained('cliente_lead')
                ->cascadeOnDelete();
            $table->foreignId('conexao_id')
                ->constrained('conexoes')
                ->cascadeOnDelete();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamp('last_outbound_at')->nullable();
            $table->string('last_inbound_event_id', 191)->nullable();
            $table->timestamps();

            $table->unique(['cliente_lead_id', 'conexao_id'], 'wcw_lead_conexao_unique');
            $table->index(['conexao_id', 'last_inbound_at'], 'wcw_conexao_inbound_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_cloud_conversation_windows');
    }
};

