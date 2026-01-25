<?php 

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\SchoolInfo;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Teacher;
use DateTime;
use TCPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use TCPDF_FONTS;

class Sf2Controller extends Controller
{
    // Define column settings
    private $column_width;
    private $margin_x = 10;
    private $column_gap = 10;

    public function index(Request $request)
    {   
        $userRole = $request->input('userRole');
        $teacher = $request->input('teacher', []);
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));
        $month_name = date('F', strtotime($year.'-'.$month.'-01'));
        $day_start = 1;
        $day_end = date('t', strtotime("$year-$month-01"));

        $comparison_date = $year . '-' . $month . '-01';

        $schoolYear = SchoolYear::whereDate('date_from', '<=', $comparison_date)
                        ->whereDate('date_to', '>=', $comparison_date)->first();
        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $height = 297;
        $width = 210;
        $y_limit = 195;
                        
        if(!$schoolYear){
            $pdfContent = $pdf->Output('', 'S'); 
            $base64PDF = base64_encode($pdfContent);
            
            return response()->json(['pdf' => $base64PDF]);
        }
        
        $school_year = $schoolYear->sy_from . ' - ' . $schoolYear->sy_to;
        $school_year_date_from = $schoolYear->date_from;
        $enrollment_end = $schoolYear->enrollment_end;

        $teacher_id = Auth::id();
        $teacher_default = Teacher::where('user_id', $teacher_id)->first();
        $grade = $teacher_default ? $teacher_default->grade : '';
        $section = $teacher_default ? $teacher_default->section : 'NONE';

        if($userRole == 1){
            if(!empty($teacher)){
                $teacher_id = $teacher['user_id'];
                $grade = $teacher['grade'];
                $section = $teacher['section'] ? $teacher['section'] : 'NONE';
            }
        }

        $schoolInfo = SchoolInfo::first();
        if($schoolInfo){
            $school_id = $schoolInfo->school_id;
            $school_name = $schoolInfo->name;
            $school_head = $schoolInfo->school_head;
            $principal = $schoolInfo->principal;
        } else {
            $school_id = '';
            $school_name = '';
            $school_head = '';
            $principal = '';
        }

        $enrollmentStats = [
            'total_enrollees' => 0,
            'total_enrollees_male' => 0,
            'total_enrollees_female' => 0,
            'late_enrollees' => 0,
            'late_enrollees_male' => 0,
            'late_enrollees_female' => 0,
            'registered_learners' => 0,
            'registered_learners_male' => 0,
            'registered_learners_female' => 0,
            'percentage_enrollment' => 0,
            'percentage_enrollment_male' => 0,
            'percentage_enrollment_female' => 0,
            'average_attendance' => 0,
            'average_attendance_male' => 0,
            'average_attendance_female' => 0,
            'percentage_attendance' => 0,
            'percentage_attendance_male' => 0,
            'percentage_attendance_female' => 0,
            'consecutive_5_absence' => 0,
            'consecutive_5_absence_male' => 0,
            'consecutive_5_absence_female' => 0,
            'dropout' => 0,
            'dropout_male' => 0,
            'dropout_female' => 0,
            'transfer_out' => 0,
            'transfer_out_male' => 0,
            'transfer_out_female' => 0,
            'transfer_in' => 0,
            'transfer_in_male' => 0,
            'transfer_in_female' => 0,
        ];

        $x =  5;
        $y = 5;
        $y_add = 0;
        $x_add = 0;

        $permissions = [
            'print' => true,
            'modify' => false,
            'copy' => false,
            'annotate' => false
        ]; 

        $pdf->SetProtection($permissions, '', null, 0, null);

        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('CDEV');
        $pdf->SetTitle('School Form 2 (SF2) Daily Attendance Report of Learners');
        $pdf->SetSubject('SF2');
        
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        $pdf->SetMargins($this->margin_x, 10, $this->margin_x);
        $pdf->SetAutoPageBreak(TRUE, 10); 

        $page_height = $pdf->getPageHeight();
        $page_width = $pdf->getPageWidth();
        $this->column_width = ($page_width - ($this->margin_x * 2) - $this->column_gap) / 2;

        $pdf->AddPage();

        //https://freefonts.co/

        $font_ttf_sansserif_file = storage_path('app/public/fonts/SansSerif.ttf');
        $sansSefif = TCPDF_FONTS::addTTFfont($font_ttf_sansserif_file, 'TrueTypeUnicode', '', 32);

        $font_ttf_sansserif_bold_file = storage_path('app/public/fonts/SansSerif-bold.ttf');
        $sansSefifBold = TCPDF_FONTS::addTTFfont($font_ttf_sansserif_bold_file, 'TrueTypeUnicode', '', 32);

        $font_ttf_sansserif_italic_file = storage_path('app/public/fonts/SansSerif-italic.ttf');
        $sansSefifItalic = TCPDF_FONTS::addTTFfont($font_ttf_sansserif_italic_file, 'TrueTypeUnicode', '', 32);
        
        $font_ttf_sansserif_bold_italic_file = storage_path('app/public/fonts/SansSerif-bold-italic.ttf');
        $sansSefifBoldItalic = TCPDF_FONTS::addTTFfont($font_ttf_sansserif_bold_italic_file, 'TrueTypeUnicode', '', 32);
        
        $this->header($pdf, $x, $y, $x_add, $sansSefif, $sansSefifBold, $sansSefifItalic, 
            $day_start, $day_end, $month_name, $school_year, $year, $month, 
            $school_id, $school_name, $grade, $section);

        $this->studentsList($pdf, $x, $y, $sansSefif, $sansSefifBold, $teacher_id, $year, $month, 
            $schoolYear, $enrollmentStats, $enrollment_end);
        
        $this->footer($pdf, $x, $y, $x_add, $sansSefif, $sansSefifBold, $sansSefifItalic, $sansSefifBoldItalic, 
            $school_head, $month_name, $school_year_date_from, $enrollmentStats);
        // $pdf->MultiCell(52, 10, "NAME\n(Last Name, First Name, Middle Name)", 1, 'C', 0, 1, '', '', true, 0, false, true, 0);

        $pdfContent = $pdf->Output('', 'S'); 
        $base64PDF = base64_encode($pdfContent);
        
        return response()->json(['pdf' => $base64PDF]);
    }

    private function header(&$pdf, &$x, &$y, $x_add, $sansSefif, $sansSefifBold, $sansSefifItalic, 
        $day_start, $day_end, $month_name, $school_year, $year, $month, 
        $school_id, $school_name, $grade, $section)
    {        

        $pdf->SetXY($x, $y);
        $pdf->SetFont($sansSefifBold, '', 13);
        $pdf->Cell(285, '', 'School Form 2 (SF2) Daily Attendance Report of Learners', 0, 1, 'C', 0, '', 1);

        $y += 10;
        $pdf->SetXY($x, $y);
        $pdf->SetFont($sansSefifItalic, '', 5);
        $pdf->Cell(285, '', '(This replaces Form 1, Form 2 & STS Form 4 - Absenteeism and Dropout Profile)', 0, 1, 'C', 0, '', 1);
        
        $y += 6;
        $pdf->SetXY($x, $y);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(66, 6, 'School ID', 0, 1, 'R', 0, '', 1);

        $x_add += 66;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 8);
        $pdf->Cell(30, 6, $school_id, 1, 1, 'C', 0, '', 1);

        $x_add += 30;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(25, 6, 'School Year', 0, 1, 'R', 0, '', 1);

        $x_add += 25;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 8);
        $pdf->Cell(32.5, 6, $school_year, 1, 1, 'C', 0, '', 1);

        $x_add += 30;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(27.5, 6, 'Report for the Month of', 0, 1, 'R', 0, '', 1);

        $x_add += 27.5;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 8);
        $pdf->Cell(30, 6, $month_name, 1, 1, 'C', 0, '', 1);

        $x_add = 0;
        $y += 6;
        $pdf->SetXY($x, $y);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(66, 6, 'Name of School', 0, 1, 'R', 0, '', 1);

        $x_add += 66;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 8);
        $pdf->Cell(87.5, 6, $school_name, 1, 1, 'C', 0, '', 1);

        $x_add += 85;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(27.5, 6, 'Grade Level', 0, 1, 'R', 0, '', 1);

        $x_add += 27.5;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 8);
        $pdf->Cell(30, 6, $grade, 1, 1, 'C', 0, '', 1);

        $x_add += 30;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(12.5, 6, 'Section', 0, 1, 'R', 0, '', 1);

        $x_add += 12.5;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 8);
        $pdf->Cell(66.5, 6, $section, 1, 1, 'C', 0, '', 1);

        $x_add = 0;
        $y += 6;
        $pdf->SetXY($x, $y);
        $pdf->SetFont($sansSefifBold, '', 6);
        $pdf->Cell(8, 10, 'No.', 1, 1, 'C', 0, '', 1);

        $x_add += 8;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefifBold, '', 6);
        $pdf->Cell(58, 5, 'NAME', 'TLR', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add, $y + 3);
        $pdf->SetFont($sansSefifBold, '', 6);
        $pdf->Cell(58, 7, '(Last Name, First Name, Middle Name)', 'BLR', 1, 'C', 0, '', 1);

        $x_add += 58;

        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(155, 3, '(1st row for date)', 1, 1, 'C', 0, '', 1);

        $x_add += 155;

        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefifBold, '', 7);
        $pdf->MultiCell(24, 6.5, "Total for the\nMonth", 1, 'C', 0, 1, '', '', true, 0, false, true, 0);

        $x_add += 24;

        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefifBold, '', 5);
        $pdf->MultiCell(42.5, 10, "REMARKS (If DROPPED OUT, state reason, please refer to legend number 2. If TRANSFERRED IN/OUT, write the name of School.)", 1, 'C', 0, 1, '', '', true, 0, false, true, 0);
        
        $y += 3;
        $x_add -= 155;
        $x_add -= 24;
        $total_days = 0;

        for ($day = $day_start; $day <= $day_end; $day++) {
            $day_abbr = date('D', strtotime($year . '-' . $month . '-' . $day));
            $day_letter = $day_abbr == 'Thu' ? strtoupper(substr($day_abbr, 0, 2)) : substr($day_abbr, 0, 1);
            if ($day_letter !== 'S') {
                $total_days++;
            }
        }

        $x_add_value = 155 / $total_days;

        for ($day = $day_start; $day <= $day_end; $day++) {
            $day_abbr = date('D', strtotime($year . '-' . $month . '-' . $day));
            $day_letter = $day_abbr == 'Thu' ? strtoupper(substr($day_abbr, 0, 2)) : substr($day_abbr, 0, 1);
            if ($day_letter !== 'S') {
                $pdf->SetXY($x + $x_add, $y);
                $pdf->SetFont($sansSefif, '', 6);
                $pdf->Cell($x_add_value, 3.5, $day, 1, 1, 'C', 0, '', 1);
                $x_add += $x_add_value;
            }
        }       
        
        $y += 3.5;
        $x_add -= 155;
        $x_add_value = 155 / $total_days;

        for ($day = $day_start; $day <= $day_end; $day++) {
            $day_abbr = date('D', strtotime($year . '-' . $month . '-' . $day));
            $day_letter = $day_abbr == 'Thu' ? strtoupper(substr($day_abbr, 0, 2)) : substr($day_abbr, 0, 1);
            if ($day_letter !== 'S') {
                $pdf->SetXY($x + $x_add, $y);
                $pdf->SetFont($sansSefif, '', 6);
                $pdf->Cell($x_add_value, 3.5, $day_letter, 1, 1, 'C', 0, '', 1);
                $x_add += $x_add_value;
            }
        }

        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefifBold, '', 5);
        $pdf->Cell(12, 3.5, 'ABSENT', 1, 1, 'C', 0, '', 1);

        $x_add += 12;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefifBold, '', 5);
        $pdf->Cell(12, 3.5, 'PRESENT', 1, 1, 'C', 0, '', 1); 

        $y += 3.5;
    }

    private function studentsList(&$pdf, &$x, &$y, $sansSefif, $sansSefifBold, $teacher_id, $year, $month, 
        $schoolYear, &$enrollmentStats, $enrollment_end)
    {
        $comparison_date = $year . '-' . $month . '-01';
        
        $sy_from = $schoolYear->sy_from;
        $sy_to = $schoolYear->sy_to;

        $students = Student::with(['schoolYearStudents' => function ($query) use ($teacher_id, $sy_from, $sy_to) {
                $query->where('sy_from', $sy_from)
                    ->where('sy_to', $sy_to)
                    ->where('teacher_id', $teacher_id);
            }])
            ->whereHas('schoolYearStudents', 
                function($query) use ($teacher_id, $sy_from, $sy_to) {
                    $query->where('sy_from', $sy_from)
                        ->where('sy_to', $sy_to)
                        ->where('teacher_id', $teacher_id);
            })->orderBy('sex', 'desc')
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        $student_ids = $students->pluck('id');

        $attendance_records = Attendance::whereIn('student_id', $student_ids)
            ->where('type', 'In')
            ->whereYear('scanned_at', $year)
            ->whereMonth('scanned_at', $month)
            ->get()
            ->groupBy('student_id')
            ->map(function ($student_attendances) {
                return $student_attendances->groupBy(function ($item) {
                    return date('j', strtotime($item->scanned_at));
                });
            });

        $male_students = $students->filter(function ($student) {
            return strtoupper($student->sex) === 'MALE' ; // Adjust 'MALE' or 'M' based on your actual data
        });

        $female_students = $students->filter(function ($student) {
            return strtoupper($student->sex) === 'FEMALE'; // Adjust 'FEMALE' or 'F' based on your actual data
        });

        $male_total = $male_students->count();
        $female_total = $female_students->count();        

        $total_days = 0;
        $year = date('Y', strtotime($comparison_date));
        $month = date('m', strtotime($comparison_date));

        for ($day = 1; $day <= date('t', strtotime($comparison_date)); $day++) {
            $day_abbr = date('D', strtotime($year . '-' . $month . '-' . $day));
            $day_letter = $day_abbr == 'Thu' ? strtoupper(substr($day_abbr, 0, 2)) : substr($day_abbr, 0, 1);
            if ($day_letter !== 'S') {
                $total_days++;
            }
        }

        $x_add_value = 155 / $total_days;        

        // --- 4. Loop Through MALE Students ---
        if ($male_total > 0) {            

            $student_index = 0;
            $total_male_absent = 0;
            $total_male_present = 0;
            $consecutive_5_absence_male = 0;
            foreach ($male_students as $student) {
                $student_attendance = $attendance_records->get($student->id, collect());
                $male_absent = 0;
                $male_present = 0;
                $this->drawStudentRow($pdf, $student, $student_attendance, $student_index, $y, $x, $x_add_value, 
                    $comparison_date, $sansSefif, $male_absent, $male_present, $consecutive_5_absence_male);

                $student_index++;
                $total_male_absent += $male_absent;
                $total_male_present += $male_present;

                $schoolYearStudent = $student->schoolYearStudents->first();

                if($schoolYearStudent){
                    if($schoolYearStudent->date_enrolled > $enrollment_end){
                        $enrollmentStats['late_enrollees'] += 1;
                        $enrollmentStats['late_enrollees_male'] += 1;
                    }                

                    if($schoolYearStudent->is_transferred == 1){
                        $enrollmentStats['transfer_in'] += 1;
                        $enrollmentStats['transfer_in_male'] += 1;
                    }

                    if(strtotime($schoolYearStudent->out_date)){
                        if(date('Y-m',strtotime($schoolYearStudent->out_date)) == date('Y-m',strtotime($comparison_date))){
                            if($schoolYearStudent->out_type == 'drop'){
                                $enrollmentStats['dropout'] += 1;
                                $enrollmentStats['dropout_male'] += 1;
                            }elseif($schoolYearStudent->out_type == 'transfer'){
                                $enrollmentStats['transfer_out'] += 1;
                                $enrollmentStats['transfer_out_male'] += 1;
                            }
                        }
                    }

                    if($consecutive_5_absence_male > 0){
                        $enrollmentStats['consecutive_5_absence'] += 1;
                        $enrollmentStats['consecutive_5_absence_male'] += 1;
                    }
                }
            }

            // Insert the MALE | TOTAL Per Day header/footer row first
            $this->drawTotalRow($pdf, '<=== MALE | TOTAL Per Day ===>', $y, $x, $x_add_value, $comparison_date, $sansSefif, 
                $male_total, $total_male_absent, $total_male_present); 
        }

        // --- 5. Loop Through FEMALE Students ---
        if ($female_total > 0) {           
            $student_index = 0;
            $total_female_absent = 0;
            $total_female_present = 0;
            $consecutive_5_absence_female = 0;
            foreach ($female_students as $student) {
                $student_attendance = $attendance_records->get($student->id, collect());
                $female_absent = 0;
                $female_present = 0;
                $this->drawStudentRow($pdf, $student, $student_attendance, $student_index, $y, $x, $x_add_value, 
                    $comparison_date, $sansSefif, $female_absent, $female_present, $consecutive_5_absence_female);
                
                    $student_index++;
                $total_female_absent += $female_absent;
                $total_female_present += $female_present;

                if($student->date_enrolled > $enrollment_end){
                    $enrollmentStats['late_enrollees'] += 1;
                    $enrollmentStats['late_enrollees_female'] += 1;
                }
                
                if($student->is_transferred == 1){
                    $enrollmentStats['transfer_in'] += 1;
                    $enrollmentStats['transfer_in_female'] += 1;
                }

                if(strtotime($student->out_date)){
                    if(date('Y-m',strtotime($student->out_date)) == date('Y-m',strtotime($comparison_date))){
                        if($student->out_type == 'drop'){
                            $enrollmentStats['dropout'] += 1;
                            $enrollmentStats['dropout_female'] += 1;
                        }elseif($student->out_type == 'transfer'){
                            $enrollmentStats['transfer_out'] += 1;
                            $enrollmentStats['transfer_out_female'] += 1;
                        }
                    }
                }

                if($consecutive_5_absence_female > 0){
                    $enrollmentStats['consecutive_5_absence'] += 1;
                    $enrollmentStats['consecutive_5_absence_female'] += 1;
                }
            }

            // Insert the FEMALE | TOTAL Per Day header/footer row
            $this->drawTotalRow($pdf, '<=== FEMALE | TOTAL Per Day ===>', $y, $x, $x_add_value, $comparison_date, $sansSefif, 
                $female_total, $total_female_absent, $total_female_present); 
        }

        $combined_total = $male_total + $female_total;
        if($combined_total > 0){
            $total_present_per_day = $total_male_present + $total_female_present;
            $total_absent_per_day = $total_male_absent + $total_female_absent;

            $this->drawTotalRow($pdf, 'Combined TOTAL Per Day', $y, $x, $x_add_value, $comparison_date, 
                $sansSefif, $combined_total, $total_absent_per_day, $total_present_per_day); 

            $enrollmentStats['total_enrollees'] = $combined_total;
            $enrollmentStats['total_enrollees_male'] = $male_total;
            $enrollmentStats['total_enrollees_female'] = $female_total;

            $enrollmentStats['registered_learners'] = $combined_total - $enrollmentStats['dropout'] - $enrollmentStats['transfer_out'];
            $enrollmentStats['registered_learners_male'] = $male_total - $enrollmentStats['dropout_male'] - $enrollmentStats['transfer_out'];
            $enrollmentStats['registered_learners_female'] = $female_total - $enrollmentStats['dropout_female'] - $enrollmentStats['transfer_out'];
        
            $enrollmentStats['percentage_enrollment'] = $enrollmentStats['total_enrollees'] > 0 ? 
                number_format(($enrollmentStats['registered_learners'] / $enrollmentStats['total_enrollees']) * 100).'%' : 0;

            $enrollmentStats['percentage_enrollment_male'] = $enrollmentStats['total_enrollees_male'] > 0 ? 
                number_format(($enrollmentStats['registered_learners_male'] / $enrollmentStats['total_enrollees_male']) * 100).'%' : 0;

            $enrollmentStats['percentage_enrollment_female'] = $enrollmentStats['total_enrollees_female'] > 0 ? 
                number_format(($enrollmentStats['registered_learners_female'] / $enrollmentStats['total_enrollees_female']) * 100).'%' : 0;
            
            $enrollmentStats['average_attendance'] = $total_days > 0 ? 
                number_format($total_present_per_day / $total_days) : 0;
            
            $enrollmentStats['average_attendance_male'] = $total_days > 0 ? 
                number_format($total_male_present / $total_days) : 0;

            $enrollmentStats['average_attendance_female'] = $total_days > 0 ? 
                number_format($total_female_present / $total_days) : 0;

            $enrollmentStats['percentage_attendance'] = $enrollmentStats['average_attendance'] > 0 ? 
                number_format(($enrollmentStats['average_attendance'] / $enrollmentStats['registered_learners']) * 100).'%' : 0;

            $enrollmentStats['percentage_attendance_male'] = $enrollmentStats['average_attendance_male'] > 0 ? 
                number_format(($enrollmentStats['average_attendance_male'] / $enrollmentStats['registered_learners_male']) * 100).'%' : 0;

            $enrollmentStats['percentage_attendance_female'] = $enrollmentStats['average_attendance_female'] > 0 ? 
                number_format(($enrollmentStats['average_attendance_female'] / $enrollmentStats['registered_learners_female']) * 100).'%' : 0;
        }
    }

    private function drawStudentRow($pdf, $student, $student_attendance, $index, &$y, $x, $x_add_value, 
        $comparison_date, $sansSefif, &$total_absent, &$total_present, &$consecutive_5_absence) {
        // Page break logic
        if ($y > 195) {
            $pdf->AddPage();
            $y = 5;
        }

        $x_add = 0;
        
        // Student Index/Number
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 7);
        $pdf->Cell(8, 6, $index + 1 . '.', 1, 1, 'C', 0, '', 1);

        // Student Full Name
        $x_add += 8;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 7);
        $extname = (!empty($student->extname)) ? ' ' . $student->extname . '.' : '';
        $full_name = strtoupper($student->lastname . ', ' . $student->firstname . $extname . ' ' . $student->middlename);
        $pdf->Cell(58, 6, $full_name, 1, 1, 'L', 0, '', 1);

        // Day Cells (Attendance Area)
        $x_add += 58;
        $year = date('Y', strtotime($comparison_date));
        $month = date('m', strtotime($comparison_date));
        $consecutive_absences = 0;

        for ($day = 1; $day <= date('t', strtotime($comparison_date)); $day++) {

            $day_abbr = date('D', strtotime($year . '-' . $month . '-' . $day));
            $day_letter = $day_abbr == 'Thu' ? strtoupper(substr($day_abbr, 0, 2)) : substr($day_abbr, 0, 1);
            
            if ($day_letter !== 'S') {

                $is_present = $student_attendance->has($day);
                $content = '';

                if (!$is_present) {
                    $content = 'X';

                    $pdf->SetTextColor(255, 0, 0);

                    $total_absent++;
                    $consecutive_absences++;

                    if ($consecutive_absences >= 5) {
                        $consecutive_5_absence++;
                    }

                }else{
                    $total_present++;
                }

                $pdf->SetXY($x + $x_add, $y);
                $pdf->SetFont($sansSefif, '', 6);
                $pdf->Cell($x_add_value, 6, $content, 1, 1, 'C', 0, '', 1);

                $pdf->SetTextColor(0, 0, 0);

                $x_add += $x_add_value;
            }
        }

        // Total Absent
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(12, 6, $total_absent > 0 ? $total_absent : '', 1, 1, 'C', 0, '', 1); // Empty cell

        // Total Present
        $x_add += 12;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(12, 6, $total_present > 0 ? $total_present : '', 1, 1, 'C', 0, '', 1); // Empty cell

        // Remarks/Notes
        $x_add += 12;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(42.5, 6, '', 1, 1, 'C', 0, '', 1); // Empty cell

        $y += 6; // Move Y down for the next row
    }

    // --- 3. Define the Drawing Logic for the Total Row (MALE/FEMALE) ---
    private function drawTotalRow($pdf, $label, &$y, $x, $x_add_value, $comparison_date, 
        $sansSefif, $total_students, $total_absent, $total_present) {
        // Page break logic (same as student row)
        if ($y > 195) {
            $pdf->AddPage();
            $y = 5;
        }

        $x_add = 0;
        
        // Label Cell: MALE / FEMALE
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 7); // Bold font for emphasis
        // The number is the total count of students in this group
        $pdf->Cell(8, 6, $total_students . '.', 1, 1, 'C', 0, '', 1); 

        // Text Cell: MALE | TOTAL Per Day or FEMALE | TOTAL Per Day
        $x_add += 8;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, '', 7);
        $pdf->Cell(58, 6, $label, 1, 1, 'C', 0, '', 1);

        // Day Cells (Attendance Area) - For daily totals
        $x_add += 58;

        $year = date('Y', strtotime($comparison_date));
        $month = date('m', strtotime($comparison_date));

        for ($day = 1; $day <= date('t', strtotime($comparison_date)); $day++) {
            $day_abbr = date('D', strtotime($year . '-' . $month . '-' . $day));
            $day_letter = $day_abbr == 'Thu' ? strtoupper(substr($day_abbr, 0, 2)) : substr($day_abbr, 0, 1);
            if ($day_letter !== 'S') { 
                $pdf->SetXY($x + $x_add, $y);
                $pdf->SetFont($sansSefif, 'B', 6);
                $pdf->Cell($x_add_value, 6, '', 1, 1, 'C', 0, '', 1); // Total cell
                $x_add += $x_add_value;
            }
        }

        // Total Absent Cell - For group total
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(12, 6, $total_absent > 0 ? $total_absent : '', 1, 1, 'C', 0, '', 1); 

        // Total Present Cell - For group total
        $x_add += 12;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(12, 6, $total_present > 0 ? $total_present : '', 1, 1, 'C', 0, '', 1);

        // Remarks/Notes Cell - Empty or for Group Totals/Notes
        $x_add += 12;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(42.5, 6, '', 1, 1, 'C', 0, '', 1);

        $y += 6; // Move Y down for the next row
    }

    private function footer(&$pdf, &$x, &$y, $x_add, $sansSefif, $sansSefifBold, $sansSefifItalic, $sansSefifBoldItalic, 
        $school_head, $month_name, $school_year_date_from, &$enrollmentStats)
    {
        // Page break logic
        if ($y > 195) {
            $pdf->AddPage();
            $y = 5;
        }

        $month_first_friday = $this->getFirstFridayNearDate($school_year_date_from);
        
        $pdf->SetXY($x, $y);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(150, 3.5, 'GUIDELINES:', 0, 1, 'L', 0, '', 1);

        $y_add = 3.5;
        $pdf->SetXY($x, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(150, 3.5, "1. The attendance shall be accomplished daily. Refer to the codes for checking learners' attendance.", 0, 1, 'L', 0, '', 1);

        $y_add += 3;
        $pdf->SetXY($x, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(150, 3, "2. Dates shall be written in the columns after Learner's Name.", 0, 1, 'L', 0, '', 1);

        $y_add += 3;
        $pdf->SetXY($x, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(150, 3, "3. To compute the following:", 0, 1, 'L', 0, '', 1);

        $y_add += 7;
        $pdf->SetXY($x + 3, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(52, 3, "a. Percentage of Enrolment =", 0, 1, 'L', 0, '', 1);

        $y_add -= 1.5;
        $pdf->SetXY($x + 55, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(70, 3, "Registered Learners as of end of the month", 'B', 1, 'C', 0, '', 1);

        $y_add += 1.5;
        $pdf->SetXY($x + 55 + 70, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(20, 3, "x 100", 0, 1, 'C', 0, '', 1);

        $y_add += 1.5;
        $pdf->SetXY($x + 55, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(70, 3, "Enrolment as of 1st Friday of the school year", 0, 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + 3, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(52, 3, "b. Average Daily Attendance =", 0, 1, 'L', 0, '', 1);

        $y_add -= 1.5;
        $pdf->SetXY($x + 55, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(70, 3, "Total Daily Attendance", 'B', 1, 'C', 0, '', 1);

        $y_add += 1.5;
        $pdf->SetXY($x + 55 + 70, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(20, 3, "", 0, 1, 'C', 0, '', 1);

        $y_add += 1.5;
        $pdf->SetXY($x + 55, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(70, 3, "Number of School Days in reporting month", 0, 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + 3, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(52, 3, "c. Percentage of Attendance for the month =", 0, 1, 'L', 0, '', 1);

        $y_add -= 1.5;
        $pdf->SetXY($x + 55, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(70, 3, "Average daily attendance", 'B', 1, 'C', 0, '', 1);

        $y_add += 1.5;
        $pdf->SetXY($x + 55 + 70, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(20, 3, "x 100", 0, 1, 'C', 0, '', 1);

        $y_add += 1.5;
        $pdf->SetXY($x + 55, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(70, 3, "Registered Learners as of end of the month", 0, 1, 'C', 0, '', 1);

        $y_add += 6;
        $pdf->SetXY($x, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->MultiCell(150, 5, "4. Every end of the month, the class adviser will submit this form to the office of the principal for recording of summary table into School Form 4. Once signed by the principal, this form should be returned to the adviser."
            , 0, 'L', 0, 1, '', '', true, 0, false, true, 0);

        $y_add += 5;
        $pdf->SetXY($x, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->MultiCell(150, 5, "5. The adviser will provide neccessary interventions including but not limited to home visitation to learner/s who were absent for 5 consecutive days and/or those at risk of dropping out."
            , 0, 'L', 0, 1, '', '', true, 0, false, true, 0);

        $y_add += 5;
        $pdf->SetXY($x, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->MultiCell(150, 5, "6.  Attendance performance of learners will be reflected in Form 137 and Form 138 every grading period."
            , 0, 'L', 0, 1, '', '', true, 0, false, true, 0);

        $y_add += 5;
        $pdf->SetXY($x + 3, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(150, 3, "*Beginning of School Year cut-off report is every 1st Friday of the School Year", 0, 1, 'L', 0, '', 1);




        $x_add += 150;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefifItalic, '', 6);
        $pdf->Cell(3, 3.5, '', 0, 1, 'L', 0, '', 1);

        $x_add += 3;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(65, 85, '', 1, 1, 'L', 0, '', 1);

        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(65, 3.5, '1. CODES FOR CHECKING ATTENDANCE', 0, 1, 'L', 0, '', 1);

        $y_add = 3;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 3, '(blank) - Present; (x)- Absent; Tardy (half shaded= Upper for', 0, 1, 'C', 0, '', 1);

        $y_add += 2.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 3, 'Late Commer, Lower for Cutting Classes)', 0, 1, 'C', 0, '', 1);

        $y_add += 4;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(65, 3.5, '2. REASONS/CAUSES FOR NLS', 0, 1, 'L', 0, '', 1);

        $y_add += 3.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(65, 3, 'a. Domestic-Related Factors', 0, 1, 'L', 0, '', 1);

        $y_add += 3;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'a.1. Had to take care of siblings', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'a.2. Early marriage/pregnancy', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, "a.3. Parents' attitude toward schooling", 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, "a.4. Family problems", 0, 1, 'L', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(65, 3, 'b. Individual-Related Factors', 0, 1, 'L', 0, '', 1);

        $y_add += 3;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'b.1. Illness', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'b.2. Overage', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'b.3. Death', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'b.4. Drug Abuse', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'b.5. Poor academic performance', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'b.6. Lack of interest/Distractions', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'b.7. Hunger/Malnutrition', 0, 1, 'L', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(65, 3, 'c. School-Related Factors', 0, 1, 'L', 0, '', 1);

        $y_add += 3;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'c.1. Teacher Factor', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'c.2. Physical condition of classroom', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'c.3. Peer influence', 0, 1, 'L', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(65, 3, 'd. Geographic/Environmental', 0, 1, 'L', 0, '', 1);

        $y_add += 3;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'd.1. Distance between home and school', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'd.2. Armed conflict (incl. Tribal wars & clanfeuds)', 0, 1, 'L', 0, '', 1);

        $y_add += 2;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'd.3. Calamities/Disasters', 0, 1, 'L', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(65, 3, 'e. Financial-Related', 0, 1, 'L', 0, '', 1);

        $y_add += 3;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(65, 2, 'e.1. Child labor, work', 0, 1, 'L', 0, '', 1);

        $y_add += 3;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(65, 3, 'f. Others (Specify)', 0, 1, 'L', 0, '', 1);

        $y_add += 13.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 7);
        $pdf->Cell(65, 4, '', 'B', 1, 'C', 0, '', 1);

        $y_add += 4;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(65, 3, 'Generated thru LIS', 0, 1, 'C', 0, '', 1);
        



        $x_add += 65;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefifItalic, '', 6);
        $pdf->Cell(3, 3.5, '', 0, 1, 'L', 0, '', 1);

        $x_add += 3;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(22, 3.5, 'Month:', 'TLR', 1, 'L', 0, '', 1);

        $y_add = 3.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(22, 3.5, '', 'LRB', 1, 'L', 0, '', 1);

        $y_add += 3.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 6);
        $pdf->Cell(44, 5, '* Enrollment as of (1st Friday of ' . $month_first_friday . ')', 'LRB', 1, 'L', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 5);
        $pdf->Cell(22, 3.5, 'Late enrolment ', 'L', 1, 'R', 0, '', 1);

        $pdf->SetXY($x + $x_add + 20, $y + $y_add);
        $pdf->SetFont($sansSefifBoldItalic, 'B', 5);
        $pdf->Cell(24, 3.5, 'during the month', 'R', 1, 'L', 0, '', 1);

        $y_add += 2.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 5);
        $pdf->Cell(44, 4, '(beyond cut-off)', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 4;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 5);
        $pdf->Cell(44, 3.5, 'Registered Learners as of', 'LR', 1, 'C', 0, '', 1);
        
        $y_add += 2.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefifBoldItalic, '', 5);
        $pdf->Cell(44, 4, 'end of month', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 4;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 5);
        $pdf->Cell(44, 3.5, 'Percentage of Enrolment as of', 'LR', 1, 'C', 0, '', 1);
        
        $y_add += 2.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefifBoldItalic, '', 5);
        $pdf->Cell(44, 4, 'end of month', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 4;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 5);
        $pdf->Cell(44, 5, 'Average Daily Attendance', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 5);
        $pdf->Cell(44, 5, 'Percentage of Attendance for the month', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 5);
        $pdf->Cell(44, 5, 'Number of students absent for 5 consecutive days', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(44, 5, 'Dropped out', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(44, 5, 'Transferred out', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(44, 5, 'Transferred in', 'LRB', 1, 'C', 0, '', 1);




        $x_add += 22;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(22, 3.5, 'No. of Days of', 'TLR', 1, 'L', 0, '', 1);        

        $y_add = 3.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(22, 3.5, 'Classes:', 'LRB', 1, 'L', 0, '', 1);

        $x_add += 22;
        $pdf->SetXY($x + $x_add, $y);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(22.5, 3.5, 'Summary', 1, 1, 'C', 0, '', 1);

        $y_add = 3.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(6, 3.5, 'M', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(6, 3.5, 'F', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 6);
        $pdf->Cell(10.5, 3.5, 'TOTAL', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 3.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['total_enrollees_male'] > 0 ? $enrollmentStats['total_enrollees_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['total_enrollees_female'] > 0 ? $enrollmentStats['total_enrollees_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 5, $enrollmentStats['total_enrollees'] > 0 ? $enrollmentStats['total_enrollees'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 6.5, $enrollmentStats['late_enrollees_male'] > 0 ? $enrollmentStats['late_enrollees_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 6.5, $enrollmentStats['late_enrollees_female'] > 0 ? $enrollmentStats['late_enrollees_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 6.5, $enrollmentStats['late_enrollees'] > 0 ? $enrollmentStats['late_enrollees'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 6.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 6.5, $enrollmentStats['registered_learners_male'] > 0 ? $enrollmentStats['registered_learners_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 6.5, $enrollmentStats['registered_learners_female'] > 0 ? $enrollmentStats['registered_learners_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 6.5, $enrollmentStats['registered_learners'] > 0 ? $enrollmentStats['registered_learners'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 6.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 6.5, $enrollmentStats['percentage_enrollment_male'] > 0 ? $enrollmentStats['percentage_enrollment_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 6.5, $enrollmentStats['percentage_enrollment_female'] > 0 ? $enrollmentStats['percentage_enrollment_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 6.5, $enrollmentStats['percentage_enrollment'] > 0 ? $enrollmentStats['percentage_enrollment'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 6.5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['average_attendance_male'] > 0 ? $enrollmentStats['average_attendance_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['average_attendance_female'] > 0 ? $enrollmentStats['average_attendance_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 5, $enrollmentStats['average_attendance'] > 0 ? $enrollmentStats['average_attendance'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['percentage_attendance_male'] > 0 ? $enrollmentStats['percentage_attendance_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['percentage_attendance_female'] > 0 ? $enrollmentStats['percentage_attendance_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 5, $enrollmentStats['percentage_attendance'] > 0 ? $enrollmentStats['percentage_attendance'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['consecutive_5_absence_male'] > 0 ? $enrollmentStats['consecutive_5_absence_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['consecutive_5_absence_female'] > 0 ? $enrollmentStats['consecutive_5_absence_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 5, $enrollmentStats['consecutive_5_absence'] > 0 ? $enrollmentStats['consecutive_5_absence'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['dropout_male'] > 0 ? $enrollmentStats['dropout_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['dropout_female'] > 0 ? $enrollmentStats['dropout_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 5, $enrollmentStats['dropout'] > 0 ? $enrollmentStats['dropout'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['transfer_out_male'] > 0 ? $enrollmentStats['transfer_out_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['transfer_out_female'] > 0 ? $enrollmentStats['transfer_out_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 5, $enrollmentStats['transfer_out'] > 0 ? $enrollmentStats['transfer_out'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 5;
        $pdf->SetXY($x + $x_add, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['transfer_in_male'] > 0 ? $enrollmentStats['transfer_in_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(6, 5, $enrollmentStats['transfer_in_female'] > 0 ? $enrollmentStats['transfer_in_female'] : '', 'LRB', 1, 'C', 0, '', 1);

        $pdf->SetXY($x + $x_add + 6 + 6, $y + $y_add);
        $pdf->SetFont($sansSefif, 'B', 5);
        $pdf->Cell(10.5, 5, $enrollmentStats['transfer_in_male'] > 0 ? $enrollmentStats['transfer_in_male'] : '', 'LRB', 1, 'C', 0, '', 1);

        $y_add += 7;
        $pdf->SetXY($x + $x_add - 44, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 6.5);
        $pdf->Cell(67.5, 5, 'I certify that this is a true and correct report.', 0, 1, 'L', 0, '', 1);

        $y_add += 7;
        $pdf->SetXY($x + $x_add - 40, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 7);
        $pdf->Cell(63.5, 5, '', 'B', 1, 'L', 0, '', 1);

        $y_add += 4;
        $pdf->SetXY($x + $x_add - 40, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(63.5, 5, '(Signature of Adviser over Printed Name)', 0, 1, 'C', 0, '', 1);

        $y_add += 7;
        $pdf->SetXY($x + $x_add - 44, $y + $y_add);
        $pdf->SetFont($sansSefifItalic, '', 7);
        $pdf->Cell(67.5, 5, 'Attested by:', 0, 1, 'L', 0, '', 1);
        
        $y_add += 9;
        $pdf->SetXY($x + $x_add - 40, $y + $y_add);
        $pdf->SetFont($sansSefifBold, '', 7);
        $pdf->Cell(63.5, 4, $school_head, 'B', 1, 'C', 0, '', 1);

        $y_add += 3;
        $pdf->SetXY($x + $x_add - 40, $y + $y_add);
        $pdf->SetFont($sansSefif, '', 5);
        $pdf->Cell(63.5, 5, '(Signature of Adviser over Printed Name)', 0, 1, 'C', 0, '', 1);
    }

    private function getFirstFridayNearDate($dateStr) {
        // Convert the input string to a DateTime object
        $date = new DateTime($dateStr);

        // Get the day of the week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        $dayOfWeek = $date->format('w');

        // Calculate the number of days to add to get to the next Friday (5 = Friday)
        $daysToAdd = (5 - $dayOfWeek + 7) % 7;

        // Add the number of days to get to the first Friday
        $date->modify("+$daysToAdd days");

        // Return the first Friday as a formatted string (YYYY-MM-DD)
        return $date->format('F');
    }
    
}