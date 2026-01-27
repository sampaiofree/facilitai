<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_lead_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_lead_id')->constrained('cliente_lead')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['cliente_lead_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_lead_tag');
    }
};
