<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('station_id')->index();
            $table->timestamp('scanned_at')->useCurrent();
            $table->enum('type', ['In', 'Out'])->index();
            $table->enum('method', ['qr', 'rfid']);
            $table->enum('status', ['success', 'error'])->index();
            $table->string('message');
            $table->unsignedBigInteger('school_year_id')->nullable()->index();
            $table->year('sy_from')->index();
            $table->year('sy_to')->index();
            $table->enum('level', ['Kinder', 'Elementary', 'Junior High School', 'Senior High School'])->index();
            $table->string('grade')->index();
            $table->string('section')->index();
            $table->unsignedBigInteger('teachers_id')->index()->nullable();
            $table->enum('message_status', ['Success', 'Error'])->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};