<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iamodelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iaplataforma_id')->constrained('iaplataformas')->cascadeOnDelete();
            $table->string('nome', 50);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['iaplataforma_id','nome']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iamodelos');
    }
};
