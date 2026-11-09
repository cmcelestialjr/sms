<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('access_code', 6)->nullable()->after('student_id');
        });

        // Auto-generate a 6-digit code for all existing students
        $students = DB::table('students')->whereNull('access_code')->get(['id']);

        foreach ($students as $student) {
            DB::table('students')
                ->where('id', $student->id)
                ->update([
                    'access_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                    'updated_at'  => now(),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('access_code');
        });
    }
};