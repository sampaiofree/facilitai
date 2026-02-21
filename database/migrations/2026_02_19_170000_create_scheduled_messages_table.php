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
        Schema::create('scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_lead_id')->constrained('cliente_lead')->cascadeOnDelete();
            $table->foreignId('assistant_id')->constrained('assistants')->cascadeOnDelete();
            $table->foreignId('conexao_id')->nullable()->constrained('conexoes')->nullOnDelete();
            $table->text('mensagem');
            $table->timestamp('scheduled_for');
            $table->string('status', 20)->default('pending');
            $table->string('event_id')->unique();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['status', 'scheduled_for'], 'scheduled_messages_status_scheduled_for_idx');
            $table->index(['cliente_lead_id', 'status', 'scheduled_for'], 'scheduled_messages_lead_status_scheduled_for_idx');
            $table->index(['assistant_id', 'status'], 'scheduled_messages_assistant_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_messages');
    }
};

