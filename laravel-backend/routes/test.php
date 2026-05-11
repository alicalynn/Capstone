<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/create-test-requests', function () {
    // Get the first karenderia
    $karenderia = DB::table('karenderias')->first();
    
    if (!$karenderia) {
        return "No karenderias found. You need to create a karenderia first.";
    }

    // Delete any existing test requests
    DB::table('ingredient_requests')->where('karenderia_id', $karenderia->id)->delete();

    // Create 3 test requests
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
            'description' => 'Mixed organic vegetables - carrots, cabbage, onions',
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

    return "✅ Created 3 test ingredient requests for karenderia: " . $karenderia->business_name . 
           "<br><br>Check the supplier app now - they should appear in the Available Requests tab!";
});
