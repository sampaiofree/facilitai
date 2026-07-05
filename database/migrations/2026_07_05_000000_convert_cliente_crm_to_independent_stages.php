<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cliente_crm_pipeline_columns')->delete();

        Schema::table('cliente_crm_pipeline_columns', function (Blueprint $table): void {
            $table->string('name')->nullable()->after('pipeline_id');
        });

        Schema::table('cliente_crm_pipeline_columns', function (Blueprint $table): void {
            $table->dropForeign(['tag_id']);
            $table->dropUnique('cliente_crm_pipeline_columns_tag_id_unique');
            $table->foreignId('tag_id')->nullable()->change();
        });

        Schema::create('cliente_crm_pipeline_leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pipeline_id')->constrained('cliente_crm_pipelines')->cascadeOnDelete();
            $table->foreignId('column_id')->constrained('cliente_crm_pipeline_columns')->cascadeOnDelete();
            $table->foreignId('cliente_lead_id')->constrained('cliente_lead')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pipeline_id', 'cliente_lead_id'], 'cliente_crm_pipeline_leads_pipeline_lead_unique');
            $table->index(['column_id', 'created_at'], 'cliente_crm_pipeline_leads_column_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_crm_pipeline_leads');

        DB::table('cliente_crm_pipeline_columns')->delete();

        Schema::table('cliente_crm_pipeline_columns', function (Blueprint $table): void {
            $table->dropColumn('name');
            $table->foreignId('tag_id')->nullable(false)->change();
            $table->unique('tag_id');
            $table->foreign('tag_id')->references('id')->on('tags')->cascadeOnDelete();
        });
    }
};
