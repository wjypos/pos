<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_extra_toping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->foreignId('extra_toping_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });        
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_extra_toping');
    }
};
