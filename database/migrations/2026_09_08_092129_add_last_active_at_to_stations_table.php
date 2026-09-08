<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('stations', function (Blueprint $table) {
            $table->dropColumn('last_active_at');
        });
    }
};
