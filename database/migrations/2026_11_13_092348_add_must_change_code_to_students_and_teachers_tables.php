<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('students', 'must_change_code')) {
            Schema::table('students', function (Blueprint $table) {
                $table->boolean('must_change_code')->default(true)->after('access_code');
            });
        }

        if (!Schema::hasColumn('teachers', 'must_change_code')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->boolean('must_change_code')->default(true)->after('access_code');
            });
        }

        // Mark existing records to require a 6-digit PIN change on first login
        DB::table('students')->update([
            'must_change_code' => true,
            'updated_at'       => now(),
        ]);

        DB::table('teachers')->update([
            'must_change_code' => true,
            'updated_at'       => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('students', 'must_change_code')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('must_change_code');
            });
        }

        if (Schema::hasColumn('teachers', 'must_change_code')) {
            Schema::table('teachers', function (Blueprint $table) {
                $table->dropColumn('must_change_code');
            });
        }
    }
};