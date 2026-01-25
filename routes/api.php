<?php

use App\Http\Controllers\Api\AbsenceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DtrController;
use App\Http\Controllers\Api\GradeController;
use App\Http\Controllers\Api\HolidayController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SchoolYearController;
use App\Http\Controllers\Api\SectionController;
use App\Http\Controllers\Api\StationController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Reports\Sf2Controller;
use App\Http\Controllers\UsersController;

Route::post('/login', [AuthController::class, 'login']);

// For Attendance Scanning
Route::post('/attendance/scan', [AttendanceController::class, 'scan']);
Route::post('/attendance/scan/qr', [AttendanceController::class, 'scanQr']);
Route::get('/attendances', [AttendanceController::class, 'index']);
Route::get('/attendance/count', [AttendanceController::class, 'count']);


//For Managing Stations
Route::get('/stations', [StationController::class, 'index']);
// Route::post('/stations', [StationController::class, 'store']);
Route::get('/stations/{id}', [StationController::class, 'show']);
// Route::put('/stations/{id}', [StationController::class, 'update']);
// Route::delete('/stations/{id}', [StationController::class, 'destroy']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/attendances/lists', [AttendanceController::class, 'lists']);
    Route::get('/attendances', [AttendanceController::class, 'index']);

    Route::get('/absences/index', [AbsenceController::class, 'index']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::get('/users', [UsersController::class, 'index']);
    Route::get('/users/roles', [UsersController::class, 'roles']);
    Route::post('/users/store', [UsersController::class, 'store']);
    Route::post('/users/update/{id}', [UsersController::class, 'update']);  
    Route::get('/teachers', [UsersController::class, 'teachers']);
    Route::get('/teachers/search', [UsersController::class, 'teachersSearch']);

    Route::get('/teachers/status-total', [TeacherController::class, 'statusTotal']);
    Route::post('/teachers/transfer', [TeacherController::class, 'transfer']);

    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'store']);
    // Route::get('/students/{id}', [StudentController::class, 'show']);
    Route::post('/students/{id}', [StudentController::class, 'update']);
    Route::delete('/students/{id}', [StudentController::class, 'destroy']);
    Route::get('/students/search', [StudentController::class, 'search']);
    Route::get('/students/status-total', [StudentController::class, 'statusTotal']);
    Route::post('/students/approved/request', [StudentController::class, 'approved']);

    Route::get('/stations-lists', [StationController::class, 'lists']);
    Route::post('/stations', [StationController::class, 'store']);
    Route::put('/stations/{id}', [StationController::class, 'update']);

    Route::get('/messages', [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);
    Route::post('/messages/resend', [MessageController::class, 'resend']);

    Route::get('/schoolYears', [SchoolYearController::class, 'index']);
    Route::post('/schoolYears', [SchoolYearController::class, 'store']);
    Route::put('/schoolYears/{id}', [SchoolYearController::class, 'update']);

    Route::get('/holidays', [HolidayController::class, 'index']);
    Route::post('/holidays', [HolidayController::class, 'store']);
    Route::put('/holidays/{id}', [HolidayController::class, 'update']);

    Route::get('/grades', [GradeController::class, 'index']);

    Route::get('/sections', [SectionController::class, 'index']);

    Route::get('/dtr', [DtrController::class, 'index']);

    Route::post('/reports/sf2', [Sf2Controller::class, 'index']);
});
