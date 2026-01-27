<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('sequence_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('sequences')->cascadeOnDelete();
            $table->unsignedInteger('ordem')->default(1);
            $table->enum('atraso_tipo', ['minuto', 'hora', 'dia'])->default('hora');
            $table->unsignedInteger('atraso_valor')->default(1);
            $table->time('janela_inicio')->nullable();
            $table->time('janela_fim')->nullable();
            $table->json('dias_semana')->nullable(); // ["mon","tue",...]
            $table->text('prompt');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('sequence_chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained('sequences')->cascadeOnDelete();
            // NOTE: `chats` is created later in the timeline. We add the FK in a follow-up migration.
            $table->foreignId('chat_id')->index();
            $table->foreignId('passo_atual_id')->nullable()->constrained('sequence_steps')->nullOnDelete();
            $table->enum('status', ['em_andamento', 'concluida', 'pausada', 'cancelada'])->default('em_andamento');
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('proximo_envio_em')->nullable();
            $table->json('tags_incluir')->nullable();
            $table->json('tags_excluir')->nullable();
            $table->string('criado_por')->nullable(); // admin|assistant|system
            $table->timestamps();
            $table->unique(['sequence_id', 'chat_id']);
        });

        Schema::create('sequence_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_chat_id')->constrained('sequence_chats')->cascadeOnDelete();
            $table->foreignId('sequence_step_id')->nullable()->constrained('sequence_steps')->nullOnDelete();
            $table->enum('status', ['sucesso', 'erro', 'pulado'])->default('sucesso');
            $table->text('message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_logs');
        Schema::dropIfExists('sequence_chats');
        Schema::dropIfExists('sequence_steps');
        Schema::dropIfExists('sequences');
    }
};
