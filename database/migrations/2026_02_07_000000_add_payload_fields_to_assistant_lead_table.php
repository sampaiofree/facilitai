<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assistant_lead', function (Blueprint $table) {
            $table->json('webhook_payload')->nullable()->after('conv_id');
            $table->json('assistant_response')->nullable()->after('webhook_payload');
            $table->text('job_message')->nullable()->after('assistant_response');
        });
    }

    public function down(): void
    {
        Schema::table('assistant_lead', function (Blueprint $table) {
            $table->dropColumn(['webhook_payload', 'assistant_response', 'job_message']);
        });
    }
};
