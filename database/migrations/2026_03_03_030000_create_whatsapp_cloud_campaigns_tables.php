<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_cloud_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->foreignId('conexao_id')->constrained('conexoes')->cascadeOnDelete();
            $table->foreignId('whatsapp_cloud_account_id')->constrained('whatsapp_cloud_accounts')->cascadeOnDelete();
            $table->foreignId('whatsapp_cloud_template_id')->constrained('whatsapp_cloud_templates')->cascadeOnDelete();
            $table->string('name', 160)->nullable();
            $table->string('mode', 20)->default('immediate');
            $table->string('status', 30)->default('draft');
            $table->timestamp('scheduled_for')->nullable();
            $table->unsignedInteger('total_leads')->default(0);
            $table->unsignedInteger('queued_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->json('filter_payload')->nullable();
            $table->json('settings')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status'], 'wcc_user_status_idx');
            $table->index(['mode', 'scheduled_for', 'status'], 'wcc_mode_scheduled_status_idx');
            $table->index(['cliente_id', 'conexao_id'], 'wcc_cliente_conexao_idx');
        });

        Schema::create('whatsapp_cloud_campaign_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_cloud_campaign_id')
                ->constrained('whatsapp_cloud_campaigns')
                ->cascadeOnDelete();
            $table->foreignId('cliente_lead_id')->constrained('cliente_lead')->cascadeOnDelete();
            $table->string('phone', 40)->nullable();
            $table->string('status', 30)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('meta_message_id', 120)->nullable();
            $table->string('idempotency_key', 120)->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->json('resolved_variables')->nullable();
            $table->text('rendered_message')->nullable();
            $table->text('error_message')->nullable();
            $table->json('meta_response')->nullable();
            $table->timestamps();

            $table->unique(
                ['whatsapp_cloud_campaign_id', 'cliente_lead_id'],
                'wcci_campaign_lead_unique'
            );
            $table->unique('idempotency_key', 'wcci_idempotency_unique');
            $table->index(['whatsapp_cloud_campaign_id', 'status'], 'wcci_campaign_status_idx');
            $table->index(['status', 'queued_at'], 'wcci_status_queued_idx');
            $table->index(['meta_message_id'], 'wcci_meta_message_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_cloud_campaign_items');
        Schema::dropIfExists('whatsapp_cloud_campaigns');
    }
};

