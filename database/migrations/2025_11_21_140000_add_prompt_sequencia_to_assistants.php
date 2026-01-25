<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->text('prompt_sequencia')->nullable()->after('prompt_aplicar_tags');
        });
    }

    public function down(): void
    {
        Schema::table('assistants', function (Blueprint $table) {
            $table->dropColumn('prompt_sequencia');
        });
    }
};
