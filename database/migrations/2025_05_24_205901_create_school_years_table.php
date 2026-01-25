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
        Schema::create('school_years', function (Blueprint $table) {
            $table->id();
            $table->year('year_from')->index();
            $table->year('year_to')->index();
            $table->integer('school_term_id')->index();
            $table->date('date_from')->index();
            $table->date('date_to');
            $table->date('enrollment_start')->nullable();
            $table->date('enrollment_end')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_years');
    }
};
