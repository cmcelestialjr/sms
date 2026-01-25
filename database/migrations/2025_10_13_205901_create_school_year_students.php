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
        Schema::create('school_year_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index();
            $table->year('sy_from')->index();
            $table->year('sy_to');
            $table->string('level')->index(); // 'Kinder','Elementary','Junior High School','Senior High School'
            $table->string('grade')->index();
            $table->string('section')->index();
            $table->string('teacher_id')->index();
            $table->enum('status', ['Active', 'Inactive'])->index();
            $table->date('date_enrolled')->index()->nullable();
            $table->integer('is_transferred')->default(0)->index(); // 0 = No, 1 = Yes
            $table->date('out_date')->index()->nullable();
            $table->string('out_type')->index()->nullable(); // Transferee, Dropout, Graduate
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_year_students');
    }
};
