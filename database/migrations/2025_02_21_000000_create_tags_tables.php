<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::create('chat_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applied_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('origem')->nullable(); // admin/tool
            $table->timestamps();

            $table->unique(['chat_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_tag');
        Schema::dropIfExists('tags');
    }
};
