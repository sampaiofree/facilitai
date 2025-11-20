<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();

            // Chave estrangeira para o usuário dono da instância/assistente
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Chave estrangeira para a instância que recebeu a mensagem
            // Essencial para saber de qual conexão veio a conversa
            $table->foreignId('instance_id')->constrained()->onDelete('cascade');

            $table->string('contact')->comment('Número do contato no WhatsApp');
            $table->string('assistant_id')->comment('ID do assistente OpenAI');
            $table->string('thread_id')->nullable()->comment('ID do thread da conversa na OpenAI');
            $table->boolean('bot_enabled')->default(true);
            
            $table->timestamps();

            // Adiciona um índice único para garantir que não haja duplicatas
            // de um mesmo contato para a mesma instância
            $table->unique(['instance_id', 'contact']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};