<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_help_section', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_help_tipo_id')
                ->constrained('prompt_help_tipo')
                ->cascadeOnDelete();
            $table->string('name');
            $table->text('descricao')->nullable();
            $table->timestamps();

            $table->unique(['prompt_help_tipo_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_help_section');
    }
};
