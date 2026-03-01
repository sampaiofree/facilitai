<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_cloud_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('label', 120)->nullable();
            $table->string('sample_value', 255)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name'], 'wccf_user_name_unique');
            $table->index(['user_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_cloud_custom_fields');
    }
};
