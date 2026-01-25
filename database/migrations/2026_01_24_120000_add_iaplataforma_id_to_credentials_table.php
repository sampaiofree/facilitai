<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->foreignId('iaplataforma_id')
                ->nullable()
                ->after('user_id')
                ->constrained('iaplataformas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credentials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('iaplataforma_id');
        });
    }
};
