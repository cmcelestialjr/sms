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
        Schema::create('school_terms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('shorten_name');
            $table->string('numeric');
            $table->string('shorten_numeric');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_terms');
    }
};
