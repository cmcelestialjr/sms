<?php 

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('student_id')->unique()->index();
            $table->string('lrn_no')->unique()->index();
            $table->string('lastname');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('extname')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('qr_code')->unique()->nullable();
            $table->string('rfid_tag')->unique()->nullable();
            $table->enum('sex', ['Male', 'Female'])->index();
            $table->date('birthdate')->nullable();
            $table->string('address')->nullable();
            $table->longText('photo')->nullable();
            $table->unsignedBigInteger('school_year_id')->nullable()->index();
            $table->year('sy_from')->index();
            $table->year('sy_to')->index();
            $table->enum('level', ['Kinder', 'Elementary', 'Junior High School', 'Senior High School'])->index();
            $table->string('grade')->index();
            $table->string('section')->index();
            $table->enum('status', ['Active', 'Inactive'])->index();
            $table->unsignedBigInteger('teachers_id')->nullable();
            $table->foreign('teachers_id')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('teacher_id_requested')->nullable();
            $table->foreign('teacher_id_requested')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('teacher_id_approved')->nullable();
            $table->foreign('teacher_id_approved')->references('id')->on('users')->onDelete('set null');
            $table->unsignedBigInteger('teacher_id_pending')->nullable();
            $table->foreign('teacher_id_pending')->references('id')->on('users')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};