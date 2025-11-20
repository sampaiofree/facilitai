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
        Schema::create('mass_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('instance_id')->constrained('instances')->onDelete('cascade');
            $table->string('nome')->nullable();
            $table->enum('tipo_envio', ['texto', 'audio'])->default('texto');
            $table->boolean('usar_ia')->default(false);
            $table->text('mensagem')->nullable();
            $table->integer('intervalo_segundos')->default(5);
            $table->integer('total_contatos')->default(0);
            $table->integer('enviados')->default(0);
            $table->integer('falhas')->default(0);
            $table->enum('status', ['pendente', 'executando', 'pausado', 'concluido'])->default('pendente');
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mass_campaigns');
    }
};
