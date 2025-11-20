<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disponibilidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chat_id')->nullable()->constrained()->nullOnDelete(); // relacionamento com o chat
            $table->date('data');          // Ex: 2025-12-05
            $table->time('inicio');        // Ex: 09:00
            $table->time('fim');           // Ex: 09:30
            $table->boolean('ocupado')->default(false);
            $table->string('nome')->nullable();       // nome do cliente
            $table->string('telefone')->nullable();   // telefone do cliente
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidades');
    }
};
