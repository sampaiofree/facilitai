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
    Schema::table('credentials', function (Blueprint $table) {
        // Adiciona a nova coluna 'label' após a coluna 'name'
        $table->string('label')->after('name');
    });
}

/**
 * Também é uma boa prática definir como reverter a migration.
 */
public function down(): void
{
    Schema::table('credentials', function (Blueprint $table) {
        $table->dropColumn('label');
    });
}
};
