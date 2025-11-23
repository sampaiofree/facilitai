<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sequence_steps', function (Blueprint $table) {
            $table->string('title', 100)->nullable()->after('sequence_id');
        });
    }

    public function down(): void
    {
        Schema::table('sequence_steps', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
