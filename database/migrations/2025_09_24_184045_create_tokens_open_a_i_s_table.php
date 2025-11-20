<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O nome da tabela será 'tokens_openai' para seguir a convenção
        Schema::create('tokens_openai', function (Blueprint $table) {
            $table->id();

            // Relacionamento com a tabela instances
            $table->foreignId('instance_id')->constrained()->onDelete('cascade');

            $table->string('conv_id')->index()->comment('ID da conversa da API');
            $table->string('contact')->index()->comment('Número do contato');
            
            // Tokens será um número inteiro e não pode ser negativo
            $table->unsignedInteger('tokens');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens_openai');
    }
};