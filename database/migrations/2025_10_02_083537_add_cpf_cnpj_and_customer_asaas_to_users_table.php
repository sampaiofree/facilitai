<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCpfCnpjAndCustomerAsaasToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Adiciona o campo cpf_cnpj
            // Pode ser string, com tamanho suficiente para CPF (11 dígitos) ou CNPJ (14 dígitos)
            $table->string('cpf_cnpj', 14)->nullable()->after('email')->comment('CPF ou CNPJ do usuário');

            // Adiciona o campo customer_asaas_id para armazenar o ID do cliente no Asaas
            $table->string('customer_asaas_id', 50)->nullable()->unique()->after('cpf_cnpj')->comment('ID do cliente Asaas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Remove os campos se a migration for revertida
            $table->dropColumn('cpf_cnpj');
            $table->dropColumn('customer_asaas_id');
        });
    }
}