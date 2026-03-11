<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo_conjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conexao_id')->constrained('conexoes')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['user_id', 'conexao_id', 'name'], 'grupo_conjuntos_user_conexao_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_conjuntos');
    }
};
