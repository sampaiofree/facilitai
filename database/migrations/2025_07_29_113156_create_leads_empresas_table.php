<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads_empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->nullable();        // Nome da empresa
            $table->string('segmento')->nullable();    // Área de atuação
            $table->string('telefone')->unique();      // Telefone único
            $table->string('cidade')->nullable();      // Cidade
            $table->string('estado', 2)->nullable();   // Sigla do estado (ex: SP, RJ)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads_empresas');
    }
};