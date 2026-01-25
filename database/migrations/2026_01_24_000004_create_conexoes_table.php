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
        Schema::create('conexoes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->text('informacoes')->nullable();
            $table->string('status')->default('pendente');
            $table->string('phone')->nullable();
            $table->string('proxy_ip')->nullable();
            $table->unsignedSmallInteger('proxy_port')->nullable();
            $table->string('proxy_username')->nullable();
            $table->string('proxy_password')->nullable();
            $table->string('proxy_provider')->nullable();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('whatsapp_api_id')->nullable()->constrained('whatsapp_api')->nullOnDelete();
            $table->string('whatsapp_api_key')->nullable();
            $table->foreignId('credential_id')->nullable()->constrained('credentials')->nullOnDelete();
            $table->foreignId('assistant_id')->nullable()->constrained('assistants')->nullOnDelete();
            $table->foreignId('model')->nullable()->constrained('iamodelos')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conexoes');
    }
};
