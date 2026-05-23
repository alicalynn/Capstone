<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KarenderiaController;
use App\Http\Controllers\MealPlanController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\MenuCategoryController;
use App\Http\Controllers\MenuItemIngredientController;
use App\Http\Controllers\IngredientController;
use App\Models\User;

// EMERGENCY LOGIN FOR PRESENTATION
Route::post('/emergency-login', function (Request $request) {
    $user = User::where('email', 'alica@gmail.com')->first();
    
    if ($user && $user->role === 'karenderia_owner') {
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'displayName' => $user->name,
                'role' => $user->role,
                'verified' => $user->verified
            ],
            'access_token' => $token,
            'token_type' => 'Bearer',
            'karenderia' => [
                'id' => $user->karenderia->id,
                'business_name' => $user->karenderia->business_name,
                'status' => $user->karenderia->status,
                'approved_at' => $user->karenderia->approved_at->format('M d, Y')
            ]
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }
    
    return response()->json(['message' => 'User not found'], 404);
});
use App\Http\Controllers\DailyMenuController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\SupplierWorkflowController;
use App\Http\Controllers\KarenderiaReviewController;
use App\Http\Controllers\IngredientRequestController;
use App\Http\Controllers\SupplierQuoteController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SupplyOrderMessageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check
Route::get('/health', function () {
    return response()->json(['status' => 'Laravel backend is running!', 'timestamp' => now()]);
});

// Dummy login route to prevent "Route [login] not defined" errors for API requests
Route::get('/login', function () {
    return response()->json(['message' => 'Unauthenticated'], 401);
})->name('login');

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/register-karenderia-owner', [AuthController::class, 'registerKarenderiaOwner']);
    Route::post('/register-supplier', [AuthController::class, 'registerSupplier']);
    Route::post('/reapply-owner', [AuthController::class, 'reapplyOwner']);
    Route::post('/reapply-supplier', [AuthController::class, 'reapplySupplier']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/change-password', [AuthController::class, 'changePassword'])->middleware('auth:sanctum');
    Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('/resend-verification', [AuthController::class, 'resendVerification'])->middleware('auth:sanctum');
    Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');
});

// User profile routes
Route::middleware('auth:sanctum')->prefix('user')->group(function () {
    Route::get('/profile', [UserController::class, 'getProfile']);
    Route::post('/profile', [UserController::class, 'updateProfile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::post('/upload-photo', [UserController::class, 'uploadPhoto']);
    Route::get('/nutritional-preferences', [UserController::class, 'getNutritionalPreferences']);
    Route::put('/nutritional-preferences', [UserController::class, 'updateNutritionalPreferences']);
    Route::delete('/account', [UserController::class, 'deleteAccount']);
});

// User-specific routes (allergens, meal plans)
Route::middleware('auth:sanctum')->prefix('users/{userId}')->group(function () {
    Route::post('/allergens', [UserController::class, 'addAllergen']);
    Route::delete('/allergens/{allergenId}', [UserController::class, 'removeAllergen']);
    Route::post('/meal-plans', [UserController::class, 'addMealPlan']);
    Route::delete('/meal-plans/{mealPlanId}', [UserController::class, 'removeMealPlan']);
    Route::put('/active-meal-plan', [UserController::class, 'setActiveMealPlan']);
});

// Karenderia routes
Route::prefix('karenderias')->group(function () {
    Route::get('/', [KarenderiaController::class, 'index']);
    Route::get('/search', [KarenderiaController::class, 'search']);
    Route::get('/nearby', [KarenderiaController::class, 'nearby']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/my-karenderia', [KarenderiaController::class, 'myKarenderia']);
        Route::put('/my-karenderia', [KarenderiaController::class, 'updateMyKarenderiaData']);
    });
    
    // Protected routes for karenderia owners (must come before {id} route)
    Route::middleware(['auth:sanctum', 'karenderia.approved'])->group(function () {
        Route::post('/', [KarenderiaController::class, 'store']);
        Route::put('/{id}', [KarenderiaController::class, 'update']);
        Route::put('/{id}/data', [KarenderiaController::class, 'updateKarenderiaData']);
        Route::delete('/{id}', [KarenderiaController::class, 'destroy']);
    });
    
    // Dynamic ID route must come AFTER specific routes to avoid conflicts
    Route::get('/{id}', [KarenderiaController::class, 'show']);
});

// Meal Plan routes
Route::prefix('meal-plans')->group(function () {
    Route::get('/', [MealPlanController::class, 'index']);
    Route::post('/', [MealPlanController::class, 'store']);
    Route::get('/{id}', [MealPlanController::class, 'show']);
    Route::put('/{id}', [MealPlanController::class, 'update']);
    Route::delete('/{id}', [MealPlanController::class, 'destroy']);
});

// Public menu routes for customers (must come BEFORE protected routes)
Route::prefix('menu-items')->group(function () {
    Route::get('/search', [MenuItemController::class, 'searchByKarenderia']); // Public endpoint for customers
    Route::get('/{id}/public', [MenuItemController::class, 'showPublic']); // Public endpoint for menu item details
});

// Menu Items routes
Route::middleware(['auth:sanctum', 'karenderia.approved'])->prefix('menu-items')->group(function () {
    Route::post('/', [MenuItemController::class, 'store']);
    Route::get('/', [MenuItemController::class, 'index']);
    Route::get('/my-menu', [MenuItemController::class, 'getMyMenuItems']); // Added missing route
    Route::get('/{id}', [MenuItemController::class, 'show']);
    Route::put('/{id}', [MenuItemController::class, 'update']);
    Route::delete('/{id}', [MenuItemController::class, 'destroy']);
});

// Menu Item Ingredients routes (for specifying what ingredients each menu item needs)
Route::middleware(['auth:sanctum', 'karenderia.approved'])->prefix('menu-item-ingredients')->group(function () {
    Route::get('/available-inventory', [MenuItemIngredientController::class, 'availableInventory']); // Get available inventory to add as ingredients
    Route::get('/{menuItemId}', [MenuItemIngredientController::class, 'index']); // Get ingredients for a menu item
    Route::post('/', [MenuItemIngredientController::class, 'store']); // Add ingredient to menu item
    Route::put('/{id}', [MenuItemIngredientController::class, 'update']); // Update ingredient quantity
    Route::delete('/{id}', [MenuItemIngredientController::class, 'destroy']); // Remove ingredient from menu item
});

// Daily Menu routes (Menu of the Day)
Route::middleware(['auth:sanctum', 'karenderia.approved'])->prefix('daily-menu')->group(function () {
    // For Karenderia Owners
    Route::get('/', [DailyMenuController::class, 'index']); // Get owner's daily menu
    Route::post('/', [DailyMenuController::class, 'store']); // Add menu item to daily menu
    Route::put('/{id}', [DailyMenuController::class, 'update']); // Update daily menu entry
    Route::delete('/{id}', [DailyMenuController::class, 'destroy']); // Remove from daily menu
    Route::get('/available-items', [DailyMenuController::class, 'getAvailableMenuItems']); // Get menu items for selection
});

// Daily Menu public routes (for customers)
Route::prefix('daily-menu')->group(function () {
    Route::get('/available', [DailyMenuController::class, 'getAvailableForCustomers']); // Get available karenderias by meal type
});

// Inventory routes (for karenderia owners to manage ingredients/supplies)
Route::middleware(['auth:sanctum', 'karenderia.approved'])->prefix('inventory')->group(function () {
    Route::get('/', [InventoryController::class, 'index']); // Get inventory items
    Route::get('/alerts', [InventoryController::class, 'lowStock']); // Get low/out of stock alerts
    Route::post('/', [InventoryController::class, 'store']); // Add inventory item
    Route::get('/{id}', [InventoryController::class, 'show']); // Get specific inventory item
    Route::put('/{id}', [InventoryController::class, 'update']); // Update inventory item
    Route::delete('/{id}', [InventoryController::class, 'destroy']); // Delete inventory item
    Route::post('/{id}/use', [InventoryController::class, 'useIngredient']); // Use ingredient in cooking
    Route::post('/{id}/restock', [InventoryController::class, 'restock']); // Restock ingredient
});

// Supplier + owner inventory workflow routes
Route::middleware(['auth:sanctum'])->prefix('supply')->group(function () {
    // Marketplace for karenderia owners
    Route::get('/marketplace', [SupplierWorkflowController::class, 'marketplace']);

    // Supplier listing management
    Route::get('/supplier/listings', [SupplierWorkflowController::class, 'supplierListings']);
    Route::post('/supplier/listings', [SupplierWorkflowController::class, 'createSupplierListing']);
    Route::put('/supplier/listings/{listingId}', [SupplierWorkflowController::class, 'updateSupplierListing']);

    // Owner Suki supplier management
    Route::get('/suki-suppliers', [SupplierWorkflowController::class, 'sukiSuppliers']);
    Route::post('/suki-suppliers/{supplierId}', [SupplierWorkflowController::class, 'markSukiSupplier']);
    Route::delete('/suki-suppliers/{supplierId}', [SupplierWorkflowController::class, 'unmarkSukiSupplier']);

    // Supply order workflow
    Route::post('/orders', [SupplierWorkflowController::class, 'createSupplyOrder']);
    Route::get('/orders/owner', [SupplierWorkflowController::class, 'ownerOrders']);
    Route::get('/orders/supplier', [SupplierWorkflowController::class, 'supplierOrders']);
    Route::get('/orders/{orderId}/messages', [SupplyOrderMessageController::class, 'index']);
    Route::post('/orders/{orderId}/messages', [SupplyOrderMessageController::class, 'store']);
    Route::delete('/orders/{orderId}/messages', [SupplyOrderMessageController::class, 'destroy']);
    Route::get('/orders/{orderId}', [SupplierWorkflowController::class, 'getOrderDetail']); // Get order detail with timeline
    Route::patch('/orders/{orderId}/status', [SupplierWorkflowController::class, 'updateOrderStatus']);
});

// ==================== INGREDIENT REQUEST SYSTEM ====================
// Ingredient Requests routes (Owner posts requests, Suppliers respond with quotes)
Route::middleware(['auth:sanctum'])->prefix('ingredient-requests')->group(function () {
    // OWNER SIDE: Create and manage ingredient requests
    Route::middleware('karenderia.approved')->group(function () {
        Route::post('/', [IngredientRequestController::class, 'store']); // Owner posts ingredient request
        Route::get('/owner/my-requests', [IngredientRequestController::class, 'ownerIndex']); // Owner view their requests
        Route::get('/owner/{ingredientRequest}', [IngredientRequestController::class, 'ownerShow']); // Owner view request detail with quotes
        Route::patch('/{ingredientRequest}/status', [IngredientRequestController::class, 'updateStatus']); // Owner update request status
    });

    // SUPPLIER SIDE: Browse and quote on requests
    Route::group(['middleware' => 'supplier.verified'], function () {
        Route::get('/supplier/available', [IngredientRequestController::class, 'supplierIndex']); // Supplier view available requests
        Route::get('/supplier/{ingredientRequest}', [IngredientRequestController::class, 'supplierShow']); // Supplier view request detail
        Route::patch('/{ingredientRequest}/mark-delivered', [IngredientRequestController::class, 'markDelivered']); // Supplier mark order as delivered
    });
});

// Supplier Quotes routes
Route::middleware(['auth:sanctum'])->prefix('supplier-quotes')->group(function () {
    // SUPPLIER: Submit quotes
    Route::middleware('supplier.verified')->group(function () {
        Route::post('/', [SupplierQuoteController::class, 'store']); // Supplier submit quote
        Route::get('/my-quotes', [SupplierQuoteController::class, 'myQuotes']); // Supplier view their quotes
    });

    // OWNER: Accept or reject quotes
    Route::middleware('karenderia.approved')->group(function () {
        Route::get('/{ingredientRequest}/all', [SupplierQuoteController::class, 'requestQuotes']); // Owner view all quotes for a request
        Route::patch('/{quote}/accept', [SupplierQuoteController::class, 'accept']); // Owner accept a quote
        Route::patch('/{quote}/reject', [SupplierQuoteController::class, 'reject']); // Owner reject a quote
    });
});

// Messages/Chat routes (for communication between owner and supplier)
Route::middleware(['auth:sanctum'])->prefix('messages')->group(function () {
    Route::post('/', [MessageController::class, 'store']); // Send a message
    Route::get('/conversations', [MessageController::class, 'conversations']); // Get all conversations
    Route::get('/ingredient-requests/{ingredientRequest}', [MessageController::class, 'getConversation']); // Get conversation for specific request
    Route::get('/unread', [MessageController::class, 'unreadCount']); // Get unread message count
    Route::post('/call-request', [MessageController::class, 'requestCall']); // Request a phone call
});

// Menu Categories (for organizing menu items)
Route::middleware(['auth:sanctum', 'karenderia.approved'])->prefix('menu-categories')->group(function () {
    Route::get('/', [MenuCategoryController::class, 'index']); // Get categories for owner's karenderia
    Route::post('/', [MenuCategoryController::class, 'store']); // Create new category
    Route::get('/{id}', [MenuCategoryController::class, 'show']); // Get specific category
    Route::put('/{id}', [MenuCategoryController::class, 'update']); // Update category
    Route::delete('/{id}', [MenuCategoryController::class, 'destroy']); // Delete category
});

// Ingredients routes (for managing ingredient database)
Route::middleware(['auth:sanctum', 'karenderia.approved'])->prefix('ingredients')->group(function () {
    Route::get('/', [IngredientController::class, 'index']); // Get all ingredients
    Route::post('/', [IngredientController::class, 'store']); // Add new ingredient
    Route::get('/{id}', [IngredientController::class, 'show']); // Get specific ingredient
    Route::put('/{id}', [IngredientController::class, 'update']); // Update ingredient
    Route::delete('/{id}', [IngredientController::class, 'destroy']); // Delete ingredient
});

// Analytics routes for karenderia owners
Route::middleware(['auth:sanctum', 'karenderia.approved'])->prefix('analytics')->group(function () {
    Route::get('/daily-sales', [MenuItemController::class, 'getDailySales']);
    Route::get('/monthly-sales', [MenuItemController::class, 'getMonthlySales']);
    Route::get('/sales-summary', [MenuItemController::class, 'getSalesSummary']);
});

// Admin routes (Protected - Admin only)
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/dashboard/stats', [AdminController::class, 'getDashboardStats']);
    
    // Karenderias Management
    Route::get('/karenderias', [AdminController::class, 'getAllKarenderias']);
    Route::get('/karenderias/{id}', [AdminController::class, 'getKarenderiaById']);
    Route::put('/karenderias/{id}', [AdminController::class, 'updateKarenderiaDetails']);
    Route::put('/karenderias/{id}/location', [AdminController::class, 'updateKarenderiaLocation']);
    Route::put('/karenderias/{id}/status', [AdminController::class, 'updateKarenderiaStatus']);
    Route::delete('/karenderias/{id}', [AdminController::class, 'deleteKarenderia']);
    Route::get('/karenderias/{id}/inventory', [AdminController::class, 'karenderiaInventory']);
    
    // Legacy routes (keeping for backward compatibility)
    Route::get('/karenderias-old', [AdminController::class, 'karenderias']);
    Route::get('/karenderias-old/{id}', [AdminController::class, 'karenderiaDetails']);
    
    // Menu Items Management - All menu items across all karenderias
    Route::get('/menu-items', [AdminController::class, 'allMenuItems']);
    
    // Sales Analytics
    Route::get('/sales-analytics', [AdminController::class, 'salesAnalytics']);
    
    // Inventory Management
    Route::get('/inventory/alerts', [AdminController::class, 'inventoryAlerts']);
    
    // User Management
    Route::get('/users', [AdminController::class, 'users']);
    Route::get('/customers', [AdminController::class, 'getCustomers']);
    Route::get('/karenderia-owners', [AdminController::class, 'getKarenderiaOwners']);
    Route::get('/suppliers', [AdminController::class, 'getSuppliers']);
    Route::put('/suppliers/{userId}/application-status', [AdminController::class, 'updateSupplierApplicationStatus']);
    Route::put('/users/{userId}/role', [AdminController::class, 'updateUserRole']);
    Route::put('/users/{userId}/toggle-status', [AdminController::class, 'toggleUserStatus']);
    Route::delete('/users/{userId}', [AdminController::class, 'deleteUser']);
    
    // Karenderia Reports (Admin moderation)
    Route::get('/reports', [KarenderiaReviewController::class, 'getReports']);
    Route::get('/reports/pending-reviews', [KarenderiaReviewController::class, 'getPendingReviews']);
    Route::patch('/reviews/{reviewId}/moderate', [KarenderiaReviewController::class, 'moderateReview']);
});

// Public Karenderia Reviews Routes (No auth required for reading)
Route::prefix('karenderia-reviews')->group(function () {
    Route::get('/{karenderiaId}', [KarenderiaReviewController::class, 'getReviews']);
});

// Authenticated Karenderia Reviews Routes
Route::middleware('auth:sanctum')->prefix('karenderia-reviews')->group(function () {
    Route::post('/{karenderiaId}', [KarenderiaReviewController::class, 'createReview']);
    Route::post('/{karenderiaId}/report', [KarenderiaReviewController::class, 'reportIssue']);
});
