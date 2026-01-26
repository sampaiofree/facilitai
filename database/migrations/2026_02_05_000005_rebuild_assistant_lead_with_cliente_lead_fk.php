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
        // Recria a tabela para corrigir o FK apontando para cliente_lead.
        Schema::dropIfExists('assistant_lead');

        Schema::create('assistant_lead', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('cliente_lead')->cascadeOnDelete();
            $table->foreignId('assistant_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('conv_id')->nullable();
            $table->timestamps();

            $table->unique(['lead_id', 'assistant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assistant_lead');
    }
};
