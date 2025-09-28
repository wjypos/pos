<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('printers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('ip_address');
            $table->string('port')->default('9100');
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->string('location')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('printers');
    }
};
