<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_identifier')->unique();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('user_id')->constrained();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->string('discount_type')->default('fixed');
            $table->decimal('platform_fee', 10, 2)->default(0);
            $table->string('platform_fee_type')->default('fixed');
            $table->decimal('final_amount', 10, 2);
            $table->string('payment_method')->nullable();
            $table->enum('status', ['completed', 'deleted'])->default('completed');
            $table->timestamp('deleted_at')->nullable();
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamp('transaction_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
