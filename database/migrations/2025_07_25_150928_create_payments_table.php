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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // Chave estrangeira para o usuário. Pode ser nula inicialmente.
            // Se o usuário for deletado, este campo se torna nulo.
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Chave estrangeira para a instância. Única e pode ser nula.
            // Se a instância for deletada, este campo se torna nulo.
            $table->foreignId('instance_id')->nullable()->unique()->constrained('instances')->onDelete('set null');
            
            $table->string('gateway')->comment('Ex: hotmart, stripe, pix_manual');
            $table->string('gateway_transaction_id')->comment('ID da transação na plataforma de pagamento');
            
            $table->string('payer_email')->comment('E-mail do pagador, usado para a vinculação inicial');
            
            $table->string('status')->default('pending')->comment('Ex: pending, paid, refunded, dispute');
            $table->decimal('amount', 10, 2)->nullable();
            
            $table->json('payload')->comment('JSON completo retornado pela plataforma de pagamento');
            
            $table->timestamps();

            // Índice único para prevenir webhooks duplicados
            $table->unique(['gateway', 'gateway_transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
