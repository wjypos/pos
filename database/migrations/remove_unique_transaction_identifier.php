<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('transactions_transaction_identifier_unique');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unique('transaction_identifier');
        });
    }
};
