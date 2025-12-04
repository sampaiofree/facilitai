<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('library_entries', function (Blueprint $table) {
            $table->string('public_edit_token', 64)->nullable()->unique();
            $table->string('public_edit_password_hash')->nullable();
            $table->boolean('public_edit_enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('library_entries', function (Blueprint $table) {
            $table->dropColumn([
                'public_edit_token',
                'public_edit_password_hash',
                'public_edit_enabled',
            ]);
        });
    }
};
