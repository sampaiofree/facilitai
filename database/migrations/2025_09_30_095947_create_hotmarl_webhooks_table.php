<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHotmarlWebhooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hotmarl_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event');
            $table->unsignedBigInteger('product_id');
            $table->string('buyer_email');
            $table->string('buyer_name');
            $table->string('buyer_first_name')->nullable();
            $table->string('buyer_last_name')->nullable();
            $table->string('buyer_checkout_phone_code')->nullable();
            $table->string('buyer_checkout_phone')->nullable();
            $table->string('status');
            $table->string('transaction')->unique(); // Coluna única para atualização
            $table->string('offer_code');
            $table->json('full_payload')->nullable(); // Para guardar o payload completo se precisar futuramente
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hotmarl_webhooks');
    }
}