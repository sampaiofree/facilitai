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
        Schema::create('assistants', function (Blueprint $table) {
            $table->id();
            
            // Dono do assistente
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Ligação com o "crédito" de pagamento que permitiu sua criação
            // Pode ser nulo para assistentes "premium" que não consomem slots
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            
            // Ligação com a credencial (chave de API) usada
            // Pode ser nulo se a credencial padrão do sistema for usada
            $table->foreignId('credential_id')->nullable()->constrained('credentials')->onDelete('set null');

            $table->string('openai_assistant_id')->unique()->comment('ID retornado pela API da OpenAI');
            $table->string('name');
            $table->text('instructions');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistants');
    }
};
