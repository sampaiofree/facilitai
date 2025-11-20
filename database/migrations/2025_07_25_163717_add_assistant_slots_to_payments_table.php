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
    Schema::table('payments', function (Blueprint $table) {
        // Define quantos assistentes este pagamento permite criar.
        $table->integer('assistant_slots')->default(1)->after('status');
    });
}

// Lembre-se de definir o método down() para reversão
public function down(): void
{
    Schema::table('payments', function (Blueprint $table) {
        $table->dropColumn('assistant_slots');
    });
}
};
