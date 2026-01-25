<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            if (!Schema::hasColumn('conexoes', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('conexoes', function (Blueprint $table) {
            if (Schema::hasColumn('conexoes', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
