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
            $table->string('student_id')->unique();
            $table->string('lastname');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('extname')->nullable();
            $table->string('email')->nullable();
            $table->string('qr_code')->unique()->nullable();
            $table->string('rfid_tag')->unique()->nullable();
            $table->enum('sex', ['Male', 'Female']);
            $table->date('birthdate')->nullable();
            $table->string('address')->nullable();
            $table->string('photo')->nullable();
            $table->enum('level', ['Kinder', 'Elementary', 'Junior High School', 'Senior High School']);
            $table->string('grade');
            $table->string('section');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};