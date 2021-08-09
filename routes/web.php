<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/home');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/refreshtoken', [App\Http\Controllers\HomeController::class, 'refreshToken']);
Route::resource('/clients', App\Http\Controllers\ExternalClientsController::class);
Route::resource('/events', App\Http\Controllers\EventController::class);

Route::resource('/profile', App\Http\Controllers\ProfileController::class);

// Get user details (for registration)
Route::post('/getuserdetails', [App\Http\Controllers\EventController::class, 'getUserDetails']);
Route::post('/registerevent', [App\Http\Controllers\EventController::class, 'registerToEvent']);

// Attendance
Route::post('/attendance/{id}', [App\Http\Controllers\EventController::class, 'attendance']);

Route::get('/attendance', function () {
    return view('attendance-queue');
});
Route::post('/attendance', [App\Http\Controllers\EventController::class, 'insertAttendanceQueue']);
