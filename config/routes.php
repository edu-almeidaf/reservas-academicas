<?php

use App\Controllers\AuthenticationsController;
use App\Controllers\HomeController;
use Core\Router\Route;

// Authentication
Route::get('/', [HomeController::class, 'index'])->name('root');

Route::get('/login', [AuthenticationsController::class, 'new'])->name('users.login');
Route::post('/login', [AuthenticationsController::class, 'authenticate'])->name('users.authenticate');

Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthenticationsController::class, 'destroy'])->name('users.logout');
});
