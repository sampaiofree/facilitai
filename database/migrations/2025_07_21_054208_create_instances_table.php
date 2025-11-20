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
    Schema::create('instances', function (Blueprint $table) {
        $table->id(); // A coluna 'id' que será o nome da instância no Evolution
        
        // Liga a instância a um usuário. Se o usuário for deletado, suas instâncias também serão.
        $table->foreignId('user_id')->constrained()->onDelete('cascade');
        
        $table->string('name'); // Nome amigável que o cliente dá
        $table->text('evolution_api_key')->nullable(); // Chave da API do Evolution (criptografada)
        $table->text('openai_api_key')->nullable(); // Chave da API da OpenAI (criptografada)
        $table->string('default_assistant_id')->nullable(); // ID do assistente padrão
        
        // Status da assinatura
        $table->string('status')->default('inactive'); // Valores: inactive, active, suspended
        
        $table->timestamp('expires_at')->nullable(); // Data de expiração da assinatura
        
        $table->timestamps(); // Cria as colunas created_at e updated_at
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instances');
    }
};
