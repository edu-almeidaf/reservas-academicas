<?php

use App\Controllers\AdminController;
use App\Controllers\AuthenticationsController;
use App\Controllers\HomeController;
use App\Controllers\ReservationsController;
use App\Controllers\ResourceImagesController;
use App\Controllers\ResourcesController;
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

    // Reservations
    Route::get('/reservations', [ReservationsController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/page/{page}', [ReservationsController::class, 'index'])->name('reservations.paginate');
    Route::get('/reservations/new', [ReservationsController::class, 'new'])->name('reservations.new');
    Route::post('/reservations', [ReservationsController::class, 'create'])->name('reservations.create');
    Route::get('/reservations/{id}', [ReservationsController::class, 'show'])->name('reservations.show');
    Route::get('/reservations/{id}/edit', [ReservationsController::class, 'edit'])->name('reservations.edit');
    Route::put('/reservations/{id}', [ReservationsController::class, 'update'])->name('reservations.update');
    Route::delete('/reservations/{id}', [ReservationsController::class, 'destroy'])->name('reservations.destroy');
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

    // Resources
    Route::get('/resources', [ResourcesController::class, 'index'])->name('resources.index');
    Route::get('/resources/{id}', [ResourcesController::class, 'show'])->name('resources.show');

    // Resource images
    Route::post('/resources/{resource_id}/images', [ResourceImagesController::class, 'create'])
        ->name('resource_images.create');
});
