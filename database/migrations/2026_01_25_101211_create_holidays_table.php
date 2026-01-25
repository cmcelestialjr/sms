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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->date('date_from')->index();
            $table->date('date_to')->index();
            $table->enum('type', ['Regular', 'Special'])->index();
            $table->enum('repeat', ['Yes', 'No'])->index();
            $table->enum('half_day', ['', 'am', 'pm'])->index();
            $table->unsignedBigInteger('updated_by')->index()->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
