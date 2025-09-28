<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pending_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_identifier')->unique();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('user_id')->constrained();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->decimal('final_amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->json('cart_data');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pending_transactions');
    }
};
