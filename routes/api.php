<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StationController;

// use App\Http\Controllers\Api\StudentController;
// use App\Http\Controllers\Api\StationController;

Route::post('/login', [AuthController::class, 'login']);

// For Attendance Scanning
Route::post('/attendance/scan', [AttendanceController::class, 'scan']);
Route::get('/attendances', [AttendanceController::class, 'index']);

// For Managing Students
// Route::get('/students', [StudentController::class, 'index']);
// Route::post('/students', [StudentController::class, 'store']);
// Route::get('/students/{id}', [StudentController::class, 'show']);
// Route::put('/students/{id}', [StudentController::class, 'update']);
// Route::delete('/students/{id}', [StudentController::class, 'destroy']);

//For Managing Stations
Route::get('/stations', [StationController::class, 'index']);
// Route::post('/stations', [StationController::class, 'store']);
Route::get('/stations/{id}', [StationController::class, 'show']);
// Route::put('/stations/{id}', [StationController::class, 'update']);
// Route::delete('/stations/{id}', [StationController::class, 'destroy']);
