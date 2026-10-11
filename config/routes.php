<?php

use App\Controllers\AdminController;
use App\Controllers\AuthenticationsController;
use App\Controllers\HomeController;
use App\Controllers\StudentController;
use App\Controllers\TeacherController;
use App\Controllers\UsersController;
use Core\Router\Route;

// Authentication
Route::get('/', [HomeController::class, 'index'])->name('root');

Route::get('/login', [AuthenticationsController::class, 'new'])->name('users.login');
Route::post('/login', [AuthenticationsController::class, 'authenticate'])->name('users.authenticate');

// Signup
Route::get('/signup', [UsersController::class, 'new'])->name('users.new');
Route::post('/signup', [UsersController::class, 'create'])->name('users.create');

Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', [AuthenticationsController::class, 'destroy'])->name('users.logout');
});

// Areas
Route::middleware('student')->group(function () {
    Route::get('/student', [StudentController::class, 'index'])->name('student.home');
});

Route::middleware('teacher')->group(function () {
    Route::get('/teacher', [TeacherController::class, 'index'])->name('teacher.home');
});

Route::middleware('admin')->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.home');
});
