<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_cloud_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_cloud_templates', 'footer_text')) {
                $table->text('footer_text')->nullable()->after('body_text');
            }

            if (!Schema::hasColumn('whatsapp_cloud_templates', 'buttons')) {
                $table->json('buttons')->nullable()->after('footer_text');
            }

            if (!Schema::hasColumn('whatsapp_cloud_templates', 'variable_examples')) {
                $table->json('variable_examples')->nullable()->after('buttons');
            }

            if (!Schema::hasColumn('whatsapp_cloud_templates', 'meta_template_id')) {
                $table->string('meta_template_id', 80)->nullable()->after('variable_examples');
            }

            if (!Schema::hasColumn('whatsapp_cloud_templates', 'last_sync_error')) {
                $table->text('last_sync_error')->nullable()->after('meta_template_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_cloud_templates', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_cloud_templates', 'last_sync_error')) {
                $table->dropColumn('last_sync_error');
            }

            if (Schema::hasColumn('whatsapp_cloud_templates', 'meta_template_id')) {
                $table->dropColumn('meta_template_id');
            }

            if (Schema::hasColumn('whatsapp_cloud_templates', 'variable_examples')) {
                $table->dropColumn('variable_examples');
            }

            if (Schema::hasColumn('whatsapp_cloud_templates', 'buttons')) {
                $table->dropColumn('buttons');
            }

            if (Schema::hasColumn('whatsapp_cloud_templates', 'footer_text')) {
                $table->dropColumn('footer_text');
            }
        });
    }
};
