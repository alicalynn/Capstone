<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\web\AdminWebController;
use App\Http\Controllers\web\PendingController;

// Serve stored permit files directly so preview/download keeps working without a storage symlink.
Route::get('/business-permits/{filename}', function (Illuminate\Http\Request $request, string $filename) {
    $permitPath = storage_path('app/public/business-permits/' . $filename);

    if (!file_exists($permitPath)) {
        abort(404, 'Business permit file not found.');
    }

    $download = filter_var($request->query('download', false), FILTER_VALIDATE_BOOLEAN);

    if ($download) {
        return response()->download($permitPath, basename($permitPath));
    }

    return response()->file($permitPath);
});

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
        Route::get('/pending/{id}/review', [PendingController::class, 'review'])->name('admin.review-application');
        Route::post('/pending/{id}/approve', [PendingController::class, 'approve'])->name('admin.pending.approve');
        Route::post('/pending/{id}/reject', [PendingController::class, 'reject'])->name('admin.pending.reject');
        Route::post('/pending/{id}/approve-with-notes', [PendingController::class, 'approveWithNotes'])->name('admin.pending.approve-with-notes');
        Route::post('/pending/{id}/reject-with-notes', [PendingController::class, 'rejectWithNotes'])->name('admin.pending.reject-with-notes');
        Route::post('/pending/user/{id}/approve', [PendingController::class, 'approveUser'])->name('admin.pending.user.approve');
        Route::post('/pending/user/{id}/reject', [PendingController::class, 'rejectUser'])->name('admin.pending.user.reject');
        
        Route::get('/users', [AdminWebController::class, 'users'])->name('admin.users');
        Route::get('/users/{id}/edit', [AdminWebController::class, 'editUser'])->name('admin.edit-user');
        Route::put('/users/{id}', [AdminWebController::class, 'updateUser'])->name('admin.update-user');
        
        Route::get('/karenderias', [AdminWebController::class, 'karenderias'])->name('admin.karenderias');
        Route::get('/karenderias/{id}/edit', [AdminWebController::class, 'editKarenderia'])->name('admin.edit-karenderia');
        Route::put('/karenderias/{id}', [AdminWebController::class, 'updateKarenderia'])->name('admin.update-karenderia');
        
        Route::post('/logout', [AdminWebController::class, 'logout'])->name('admin.logout');
    });
});
