<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('home');
Route::get('/app', [AppController::class, 'index'])->name('app');
Route::get('/app/print', [AppController::class, 'print'])->name('app.print');
Route::get('/app/dashboard', [AppController::class, 'dashboard'])->name('app.dashboard');
Route::post('/track', [TrackController::class, 'store'])->name('track');
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware('auth.basic')
    ->name('admin');

Route::get('/privacy-policy', function () {
    return view('privacy');
})->name('privacy');
