<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\SchoolYearStudent;
use App\Models\SchoolYear;
use App\Models\Teacher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        // 1. Get the latest school year based on your specific sorting requirements
        $latestSchoolYear = SchoolYear::orderByDesc('sy_to')
            ->orderByDesc('school_term_id')
            ->first();

        if (!$latestSchoolYear) {
            throw new \Exception('No School Year found in the database. Please create one first.');
        }

        // 2. Wrap in a database transaction. If one row fails, nothing gets inserted (prevents partial dirty data).
        DB::transaction(function () use ($rows, $latestSchoolYear) {
            foreach ($rows as $row) {
                // Skip empty rows
                if (!isset($row['student_id'])) {
                    continue;
                }

                $teacher = Teacher::where('user_id', $row['teacher_id'] ?? null)->first();

                $school_year_id = $teacher ? $teacher->school_year_id : $latestSchoolYear->id;
                $sy_from = $teacher ? $teacher->sy_from : $latestSchoolYear->sy_from;
                $sy_to = $teacher ? $teacher->sy_to : $latestSchoolYear->sy_to;
                $level = $teacher ? $teacher->level : null;
                $grade = $teacher ? $teacher->grade : null;
                $section = $teacher ? $teacher->section : null;
                $birthdate = empty($row['birthdate']) ? null : str_replace("'", "", $row['birthdate']);

                // 3. Create or Update the Student
                // Assuming 'student_id' is the unique identifier in your excel file
                $student = Student::updateOrCreate(
                    ['lrn_no' => $row['student_id']], 
                    [
                        'student_id'        => $row['student_id'],
                        'lastname'   => isset($row['lastname']) ? mb_strtoupper(str_replace("'", "", trim($row['lastname']))) : null,
                        'firstname'  => isset($row['firstname']) ? mb_strtoupper(str_replace("'", "", trim($row['firstname']))) : null,
                        'middlename' => isset($row['middlename']) ? mb_strtoupper(str_replace("'", "", trim($row['middlename']))) : null,
                        'extname'    => isset($row['extname']) ? mb_strtoupper(str_replace("'", "", trim($row['extname']))) : null,
                        'contact_no'     => '0'.$row['guardian_contact_no'] ?? null,
                        'email'          => $row['guardian_email'] ?? null,
                        'sex'            => $row['sex'] ?? 'Female',
                        'qr_code'        => $row['student_id'],
                        'teachers_id'    => $row['teacher_id'],
                        'birthdate'      => $birthdate,
                        // Link to the latest school year retrieved above
                        'school_year_id' => $school_year_id,
                        'sy_from'        => $sy_from,
                        'sy_to'          => $sy_to,                        
                        'level'          => $level,
                        'grade'          => $grade,
                        'section'        => $section,
                        'status'         => 'Active', // Default status assumption                        
                    ]
                );

                // 4. Create or Update Guardian (if provided in excel)
                if (isset($row['guardian_name'])) {
                    StudentGuardian::updateOrCreate(
                        ['student_id' => $student->id],
                        [
                            'name' => isset($row['guardian_name']) ? mb_strtoupper(str_replace("'", "", trim($row['guardian_name']))) : null,
                            'contact_no' => '0'.$row['guardian_contact_no'] ?? null,
                            'email'      => $row['guardian_email'] ?? null,
                        ]
                    );
                }

                // 5. Create the SchoolYearStudent pivot/history record
                SchoolYearStudent::updateOrCreate(
                    [
                        'student_id'     => $student->id,
                        'school_year_id' => $school_year_id,
                    ],
                    [
                        'sy_from'        => $sy_from,
                        'sy_to'          => $sy_to,
                        'teacher_id'     => $row['teacher_id'],
                        'level'          => $level,
                        'grade'          => $grade,
                        'section'        => $section,
                        'status'         => 'Active',
                    ]
                );
            }
        });
    }
}