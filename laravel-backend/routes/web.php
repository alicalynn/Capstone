<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\web\AdminWebController;
use App\Http\Controllers\web\PendingController;
use App\Http\Controllers\Web\OwnerIngredientRequestController;
use App\Http\Controllers\Web\OwnerSupplierQuoteController;

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

// Dummy login route for authentication middleware fallback
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');

// Admin Web Interface Routes
Route::prefix('admin')->group(function () {
    // Admin login
    Route::get('/login', [AdminWebController::class, 'loginForm'])->name('admin.login');
    Route::post('/login', [AdminWebController::class, 'login'])->name('admin.login.post');
    
    // Protected admin routes
    Route::middleware(['auth.admin'])->group(function () {
        Route::get('/dashboard', [AdminWebController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/reports', [AdminWebController::class, 'reports'])->name('admin.reports');
        Route::get('/pending', [PendingController::class, 'index'])->name('admin.pending');
        Route::get('/pending/permit/{id}', [PendingController::class, 'businessPermit'])->name('admin.pending.permit');
        Route::get('/pending/{id}/review', [PendingController::class, 'review'])->name('admin.review-application');
        Route::post('/pending/{id}/approve', [PendingController::class, 'approve'])->name('admin.pending.approve');
        Route::post('/pending/{id}/reject', [PendingController::class, 'reject'])->name('admin.pending.reject');
        Route::post('/pending/{id}/approve-with-notes', [PendingController::class, 'approveWithNotes'])->name('admin.pending.approve-with-notes');
        Route::post('/pending/{id}/reject-with-notes', [PendingController::class, 'rejectWithNotes'])->name('admin.pending.reject-with-notes');
        Route::post('/pending/user/{id}/approve', [PendingController::class, 'approveUser'])->name('admin.pending.user.approve');
        Route::post('/pending/user/{id}/reject', [PendingController::class, 'rejectUser'])->name('admin.pending.user.reject');
        
        // Customer Reviews Moderation
        Route::get('/reviews', [PendingController::class, 'reviews'])->name('admin.reviews');
        Route::post('/reviews/{id}/approve', [PendingController::class, 'approveReview'])->name('admin.reviews.approve');
        Route::post('/reviews/{id}/reject', [PendingController::class, 'rejectReview'])->name('admin.reviews.reject');
        
        Route::get('/users', [AdminWebController::class, 'users'])->name('admin.users');
        Route::get('/users/{id}/edit', [AdminWebController::class, 'editUser'])->name('admin.edit-user');
        Route::put('/users/{id}', [AdminWebController::class, 'updateUser'])->name('admin.update-user');
        Route::put('/users/{id}/approve', [AdminWebController::class, 'approveUser'])->name('admin.users.approve');
        
        Route::get('/karenderias', [AdminWebController::class, 'karenderias'])->name('admin.karenderias');
        Route::get('/karenderias/{id}/edit', [AdminWebController::class, 'editKarenderia'])->name('admin.edit-karenderia');
        Route::put('/karenderias/{id}', [AdminWebController::class, 'updateKarenderia'])->name('admin.update-karenderia');
        
        Route::post('/logout', [AdminWebController::class, 'logout'])->name('admin.logout');
    });
});

// Owner Web Interface Routes
Route::prefix('owner')->middleware(['auth:sanctum', 'karenderia.approved'])->group(function () {
    // Ingredient Requests
    Route::get('/ingredient-requests', [OwnerIngredientRequestController::class, 'index'])->name('owner.ingredient-requests');
    Route::get('/ingredient-requests/create', [OwnerIngredientRequestController::class, 'create'])->name('owner.ingredient-requests.create');
    Route::post('/ingredient-requests', [OwnerIngredientRequestController::class, 'store'])->name('owner.ingredient-requests.store');
    Route::get('/ingredient-requests/{ingredientRequest}', [OwnerIngredientRequestController::class, 'show'])->name('owner.ingredient-requests.show');
    Route::patch('/ingredient-requests/{ingredientRequest}/status', [OwnerIngredientRequestController::class, 'updateStatus'])->name('owner.ingredient-requests.update-status');

    // Supplier Quotes
    Route::patch('/supplier-quotes/{quote}/accept', [OwnerSupplierQuoteController::class, 'accept'])->name('owner.supplier-quotes.accept');
    Route::patch('/supplier-quotes/{quote}/reject', [OwnerSupplierQuoteController::class, 'reject'])->name('owner.supplier-quotes.reject');
});

// TEST ROUTE - Create sample ingredient requests for testing
Route::get('/test/create-sample-requests', function () {
    $karenderia = DB::table('karenderias')->first();
    
    if (!$karenderia) {
        return "❌ No karenderias found. You need to create a karenderia first.";
    }

    DB::table('ingredient_requests')->where('karenderia_id', $karenderia->id)->delete();

    $requests = [
        [
            'karenderia_id' => $karenderia->id,
            'title' => 'Fresh Chicken Breast',
            'description' => 'High quality chicken breasts, boneless and skinless',
            'ingredient_type' => 'Meat',
            'needed_quantity' => 10,
            'unit' => 'kg',
            'needed_by_date' => now()->addDays(3)->format('Y-m-d'),
            'budget' => 500,
            'status' => 'open',
            'delivery_address' => $karenderia->address ?? 'Main Street',
            'expiry_hours' => 48,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'karenderia_id' => $karenderia->id,
            'title' => 'Organic Vegetables',
            'description' => 'Mixed organic vegetables',
            'ingredient_type' => 'Produce',
            'needed_quantity' => 25,
            'unit' => 'kg',
            'needed_by_date' => now()->addDays(2)->format('Y-m-d'),
            'budget' => 300,
            'status' => 'open',
            'delivery_address' => $karenderia->address ?? 'Main Street',
            'expiry_hours' => 48,
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'karenderia_id' => $karenderia->id,
            'title' => 'Whole Milk - 1L Bottles',
            'description' => 'Fresh whole milk in 1-liter bottles',
            'ingredient_type' => 'Dairy',
            'needed_quantity' => 50,
            'unit' => 'liters',
            'needed_by_date' => now()->addDays(1)->format('Y-m-d'),
            'budget' => 400,
            'status' => 'open',
            'delivery_address' => $karenderia->address ?? 'Main Street',
            'expiry_hours' => 48,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ];

    DB::table('ingredient_requests')->insert($requests);

    return "✅ Created 3 test ingredient requests!<br><br>
            Karenderia: <strong>" . $karenderia->business_name . "</strong><br>
            <br>
            Now check the supplier app - requests should appear in the Available Requests tab!<br>
            <br>
            <a href='/test/check-requests' style='color:blue;text-decoration:underline'>Check requests in database</a>";
});

// TEST ROUTE - Check what requests exist
Route::get('/test/check-requests', function () {
    $requests = DB::table('ingredient_requests')->get();
    return "Total requests in database: " . count($requests) . "<br><br>" . $requests->toJson();
});
