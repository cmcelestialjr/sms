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
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->string('lastname');
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('extname')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('address')->nullable();
            $table->enum('sex', ['Male', 'Female'])->index();
            $table->string('position')->nullable();
            $table->year('sy_from')->index();
            $table->year('sy_to')->index();
            $table->enum('level', ['Kinder', 'Elementary', 'Junior High School', 'Senior High School'])->index();
            $table->string('grade')->index();
            $table->string('section')->index();
            $table->enum('status', ['Active', 'Inactive'])->index();
            $table->longText('photo')->nullable();
            $table->unsignedBigInteger('school_year_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
