<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cliente_crm_columns');

        Schema::create('cliente_crm_pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->index(['cliente_id', 'position'], 'cliente_crm_pipelines_cliente_position_idx');
        });

        Schema::create('cliente_crm_pipeline_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('cliente_crm_pipelines')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();

            $table->unique('tag_id');
            $table->unique(['pipeline_id', 'position']);
            $table->index(['pipeline_id', 'position'], 'cliente_crm_pipeline_columns_pipeline_position_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_crm_pipeline_columns');
        Schema::dropIfExists('cliente_crm_pipelines');
    }
};
