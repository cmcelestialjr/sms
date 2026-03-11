<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up(): void
    {
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('station_name')->index();
            $table->string('ipaddress')->unique();
            $table->string('location')->nullable();
            $table->tinyInteger('is_mobile')->index()->nullable();
            $table->timestamps();

            $table->index(['ipaddress', 'location']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};