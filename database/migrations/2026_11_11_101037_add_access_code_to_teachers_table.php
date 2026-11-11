<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('access_code', 6)->nullable()->after('id_no');
        });

        // Auto-generate a 6-digit access code for all existing teachers
        $teachers = DB::table('teachers')->whereNull('access_code')->get(['id']);

        foreach ($teachers as $teacher) {
            DB::table('teachers')
                ->where('id', $teacher->id)
                ->update([
                    'access_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                    'updated_at'  => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('access_code');
        });
    }
};