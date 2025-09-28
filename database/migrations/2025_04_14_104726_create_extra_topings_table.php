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
        Schema::create('extra_topings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price_offline', 10, 2);
            $table->decimal('price_online', 10, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_topings');
    }
};
