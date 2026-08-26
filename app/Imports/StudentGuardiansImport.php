<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentGuardian;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentGuardiansImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // Wrap in a database transaction so if one row fails, nothing gets inserted
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                // Skip empty rows
                if (!isset($row['student_id'])) {
                    continue;
                }

                // 1. Look up the actual student in the database using the Excel's student_id
                // We check both 'student_id' and 'lrn_no' just in case[cite: 1, 2]
                $student = Student::where('student_id', $row['student_id'])
                                  ->orWhere('lrn_no', $row['student_id'])
                                  ->first();

                // 2. Only create/update the guardian if the student actually exists in the system
                if ($student) {
                    StudentGuardian::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'name'       => isset($row['name']) ? mb_strtoupper(str_replace("'", "", trim($row['name']))) : null,
                        ], // Use the internal DB ID for the foreign key
                        [                            
                            'email'      => $row['email'] ?? null,
                            'contact_no' => isset($row['contact_no']) ? '0' . $row['contact_no'] : null,
                        ]
                    );
                }
            }
        });
    }
}