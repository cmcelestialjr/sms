<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('stations', 'name')) {
            Schema::table('stations', function (Blueprint $table) {
                $table->string('name')->nullable()->after('uuid');
            });
        }

        // Bump updated_at so attendify:sync-cloud picks up the stations table automatically
        DB::table('stations')->update([
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('stations', 'name')) {
            Schema::table('stations', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }
    }
};