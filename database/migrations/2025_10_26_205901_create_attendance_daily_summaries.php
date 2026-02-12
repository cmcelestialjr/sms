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
        Schema::create('attendance_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index();
            $table->date('date')->index();
            $table->time('actual_am_in')->nullable();
            $table->time('actual_am_out')->nullable();
            $table->time('actual_pm_in')->nullable();
            $table->time('actual_pm_out')->nullable();
            $table->unsignedBigInteger('school_year_id')->nullable()->index();
            $table->year('sy_from')->index();
            $table->year('sy_to')->index();
            $table->enum('level', ['Kinder', 'Elementary', 'Junior High School', 'Senior High School'])->index();
            $table->string('grade')->index();
            $table->string('section')->index();
            $table->unsignedBigInteger('teachers_id')->index()->nullable();
            $table->integer('is_late')->index(); //0 = no, 1 = yes
            $table->integer('is_undertime')->index(); //0 = no, 1 = yes
            $table->integer('is_excused')->index(); //0 = no, 1 = yes
            $table->integer('is_absent')->nullable()->index(); //null = present, 1 = absent
            $table->string('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_daily_summaries');
    }
};
