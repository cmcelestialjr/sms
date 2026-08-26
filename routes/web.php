<?php

use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\StudentImportController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/students/import', [StudentImportController::class, 'index'])->name('students.import.index');
Route::post('/students/import', [StudentImportController::class, 'import'])->name('students.import.process');
Route::post('/import-guardians', [StudentImportController::class, 'importGuardians'])->name('guardians.import.process');
// Route::get('/attendances123/scans123/{id}/{code}', [AttendanceController::class, 'scan']);

Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/attendance/rfid/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/attendance/qr/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/attendance/qr/{id}/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/attendances/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/dashboard/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/users/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/students/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/stations-lists/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/station-list/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/stations/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/messages/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/schoolYears/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/holidays/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/dtr/{any?}', function () {
    return view('app');
})->where('any', '.*');

Route::get('/reports/{any?}', function () {
    return view('app');
})->where('any', '.*');
