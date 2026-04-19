<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\web\AdminWebController;
use App\Http\Controllers\web\PendingController;

// Redirect root URL to admin login
Route::get('/', function () {
    return redirect('/admin/login');
});

// Admin Web Interface Routes
Route::prefix('admin')->group(function () {
    // Admin login
    Route::get('/login', [AdminWebController::class, 'loginForm'])->name('admin.login');
    Route::post('/login', [AdminWebController::class, 'login'])->name('admin.login.post');
    
    // Protected admin routes
    Route::middleware(['auth.admin'])->group(function () {
        Route::get('/dashboard', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/pending', [PendingController::class, 'index'])->name('admin.pending');
        Route::get('/pending/permit/{id}', [PendingController::class, 'businessPermit'])->name('admin.pending.permit');
        Route::post('/pending/{id}/approve', [PendingController::class, 'approve'])->name('admin.pending.approve');
        Route::post('/pending/{id}/reject', [PendingController::class, 'reject'])->name('admin.pending.reject');
        Route::post('/pending/user/{id}/approve', [PendingController::class, 'approveUser'])->name('admin.pending.user.approve');
        Route::post('/pending/user/{id}/reject', [PendingController::class, 'rejectUser'])->name('admin.pending.user.reject');
        Route::get('/users', [AdminWebController::class, 'users'])->name('admin.users');
        Route::get('/karenderias', [AdminWebController::class, 'karenderias'])->name('admin.karenderias');
        Route::post('/logout', [AdminWebController::class, 'logout'])->name('admin.logout');
    });
});
