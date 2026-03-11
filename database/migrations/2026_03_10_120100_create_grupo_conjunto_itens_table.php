<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupo_conjunto_itens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_conjunto_id')->constrained('grupo_conjuntos')->cascadeOnDelete();
            $table->string('group_jid');
            $table->string('group_name')->nullable();
            $table->timestamps();

            $table->unique(['grupo_conjunto_id', 'group_jid'], 'grupo_conjunto_itens_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupo_conjunto_itens');
    }
};
