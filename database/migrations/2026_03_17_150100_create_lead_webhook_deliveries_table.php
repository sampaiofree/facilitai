<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_webhook_link_id')->constrained('lead_webhook_links')->cascadeOnDelete();
            $table->string('status')->default('failed');
            $table->json('payload');
            $table->string('payload_hash', 64)->index();
            $table->foreignId('cliente_lead_id')->nullable()->constrained('cliente_lead')->nullOnDelete();
            $table->string('resolved_phone')->nullable();
            $table->json('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['lead_webhook_link_id', 'created_at'], 'lead_webhook_deliveries_link_created_idx');
            $table->index(['lead_webhook_link_id', 'payload_hash', 'created_at'], 'lead_webhook_deliveries_link_hash_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_webhook_deliveries');
    }
};
