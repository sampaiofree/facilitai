<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAsaasWebhooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asaas_webhooks', function (Blueprint $table) {
            $table->id(); // ID primário auto-incremento
            $table->string('webhook_id', 50)->unique()->comment('ID do evento do webhook Asaas (ex: evt_...)');
            $table->string('event_type')->comment('Tipo do evento (ex: PAYMENT_RECEIVED)');
            $table->timestamp('webhook_created_at')->comment('Data de criação do evento do webhook no Asaas');

            // Campos do objeto 'payment' (podem ser nulos se o evento não for de pagamento)
            $table->string('payment_id', 50)->nullable()->comment('ID do pagamento no Asaas (ex: pay_...)');
            $table->date('payment_created_at')->nullable()->comment('Data de criação do pagamento no Asaas');
            $table->string('customer_id', 50)->nullable()->comment('ID do cliente no Asaas (ex: cus_...)');
            $table->decimal('value', 10, 2)->nullable()->comment('Valor do pagamento');
            $table->string('description')->nullable()->comment('Descrição do pagamento'); // Alterado para string, se a descrição for curta. Se for muito longa, use text()
            $table->string('billing_type')->nullable()->comment('Tipo de cobrança (ex: PIX, BOLETO)');
            $table->date('confirmed_at')->nullable()->comment('Data de confirmação do pagamento');
            $table->string('status')->nullable()->comment('Status do pagamento (ex: RECEIVED)');
            $table->date('payment_at')->nullable()->comment('Data em que o pagamento foi efetivado');
            $table->date('client_payment_at')->nullable()->comment('Data em que o cliente realizou o pagamento');
            $table->string('invoice_url')->nullable()->comment('URL da fatura');
            $table->string('external_reference')->nullable()->comment('Referência externa');
            $table->string('transaction_receipt_url')->nullable()->comment('URL do comprovante de transação');
            $table->string('nosso_numero')->nullable()->comment('Nosso Número para boletos');

            // Payload completo (JSON)
            $table->json('payload')->comment('Payload JSON completo do webhook');

            $table->timestamps(); // Adiciona created_at e updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asaas_webhooks');
    }
}