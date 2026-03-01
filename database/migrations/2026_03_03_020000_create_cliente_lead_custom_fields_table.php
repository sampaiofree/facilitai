<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_lead_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_lead_id')->constrained('cliente_lead')->cascadeOnDelete();
            $table->foreignId('whatsapp_cloud_custom_field_id')->constrained('whatsapp_cloud_custom_fields')->cascadeOnDelete();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(
                ['cliente_lead_id', 'whatsapp_cloud_custom_field_id'],
                'clcf_lead_field_unique'
            );
            $table->index('whatsapp_cloud_custom_field_id', 'clcf_field_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_lead_custom_fields');
    }
};
