<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo_conjunto_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('grupo_conjunto_id')->constrained('grupo_conjuntos')->cascadeOnDelete();
            $table->foreignId('conexao_id')->constrained('conexoes')->cascadeOnDelete();
            $table->text('mensagem');
            $table->string('dispatch_type', 20)->default('scheduled');
            $table->timestamp('scheduled_for')->nullable();
            $table->string('status', 20)->default('pending');
            $table->json('recipients');
            $table->json('result')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_for'], 'gcm_status_scheduled_for_idx');
            $table->index(['grupo_conjunto_id', 'created_at'], 'gcm_conjunto_created_at_idx');
            $table->index(['user_id', 'status', 'scheduled_for'], 'gcm_user_status_scheduled_for_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_conjunto_mensagens');
    }
};
