<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login/{any?}', function () {
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
