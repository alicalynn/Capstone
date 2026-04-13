<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DailyMenu;
use App\Models\Karenderia;
use App\Models\MenuItem;
use Carbon\Carbon;

class DailyMenuController extends Controller
{
    /**
     * Get daily menu for karenderia owner (their own karenderias)
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Get the user's karenderia
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            
            if (!$karenderia) {
                return response()->json(['error' => 'No karenderia found for this user'], 403);
            }

            $date = $request->get('date', Carbon::today()->toDateString());
            $mealType = $request->get('meal_type');

            $query = DailyMenu::with(['menuItem', 'karenderia'])
                ->where('karenderia_id', $karenderia->id)
                ->where('date', $date);

            if ($mealType) {
                $query->where('meal_type', $mealType);
            }

            $dailyMenus = $query->orderBy('meal_type')->orderBy('created_at')->get();

            return response()->json([
                'data' => $dailyMenus,
                'date' => $date,
                'karenderia' => $karenderia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch daily menu',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new daily menu entry
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Get the user's karenderia
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            
            if (!$karenderia) {
                return response()->json(['error' => 'No karenderia found for this user'], 403);
            }

            $validatedData = $request->validate([
                'menu_item_id' => 'required|exists:menu_items,id',
                'date' => 'required|date|after_or_equal:today',
                'meal_type' => 'required|in:breakfast,lunch,dinner',
                'quantity' => 'required|integer|min:1',
                'special_price' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500'
            ]);

            // Verify the menu item belongs to this karenderia
            $menuItem = MenuItem::where('id', $validatedData['menu_item_id'])
                ->where('karenderia_id', $karenderia->id)
                ->first();

            if (!$menuItem) {
                return response()->json(['error' => 'Menu item not found or does not belong to your karenderia'], 403);
            }

            // Check if daily menu entry already exists
            $existingEntry = DailyMenu::where('karenderia_id', $karenderia->id)
                ->where('menu_item_id', $validatedData['menu_item_id'])
                ->where('date', $validatedData['date'])
                ->where('meal_type', $validatedData['meal_type'])
                ->first();

            if ($existingEntry) {
                $additionalQty = (int) $validatedData['quantity'];
                $existingEntry->quantity = (int) $existingEntry->quantity + $additionalQty;
                $existingEntry->original_quantity = (int) $existingEntry->original_quantity + $additionalQty;

                if (array_key_exists('special_price', $validatedData)) {
                    $existingEntry->special_price = $validatedData['special_price'];
                }

                if (array_key_exists('notes', $validatedData)) {
                    $existingEntry->notes = $validatedData['notes'];
                }

                $existingEntry->is_available = true;
                $existingEntry->save();
                $existingEntry->load(['menuItem', 'karenderia']);

                return response()->json([
                    'message' => 'Existing daily menu item updated with additional quantity',
                    'data' => $existingEntry
                ]);
            }

            // Create daily menu entry
            $dailyMenu = DailyMenu::create([
                'karenderia_id' => $karenderia->id,
                'menu_item_id' => $validatedData['menu_item_id'],
                'date' => $validatedData['date'],
                'meal_type' => $validatedData['meal_type'],
                'quantity' => $validatedData['quantity'],
                'original_quantity' => $validatedData['quantity'],
                'special_price' => $validatedData['special_price'],
                'notes' => $validatedData['notes'] ?? null
            ]);

            // Load relationships
            $dailyMenu->load(['menuItem', 'karenderia']);

            return response()->json([
                'message' => 'Daily menu item added successfully',
                'data' => $dailyMenu
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create daily menu entry',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing daily menu entry
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $dailyMenu = DailyMenu::findOrFail($id);
            
            // Get the user's karenderia
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            
            if (!$karenderia || $dailyMenu->karenderia_id !== $karenderia->id) {
                return response()->json(['error' => 'Unauthorized: You can only update your own daily menu items'], 403);
            }

            $validatedData = $request->validate([
                'quantity' => 'sometimes|integer|min:0',
                'is_available' => 'sometimes|boolean',
                'special_price' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string|max:500'
            ]);

            // If updating quantity, ensure it's not less than what's already been ordered
            if (isset($validatedData['quantity'])) {
                $orderedQuantity = $dailyMenu->original_quantity - $dailyMenu->quantity;
                if ($validatedData['quantity'] < $orderedQuantity) {
                    return response()->json([
                        'error' => "Cannot set quantity below $orderedQuantity (already ordered amount)"
                    ], 400);
                }
                // Update the available quantity
                $dailyMenu->quantity = $validatedData['quantity'] - $orderedQuantity;
                $dailyMenu->original_quantity = $validatedData['quantity'];
                unset($validatedData['quantity']); // Remove from mass update
            }

            $dailyMenu->update($validatedData);
            $dailyMenu->load(['menuItem', 'karenderia']);

            return response()->json([
                'message' => 'Daily menu item updated successfully',
                'data' => $dailyMenu
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update daily menu entry',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a daily menu entry
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $dailyMenu = DailyMenu::findOrFail($id);
            
            // Get the user's karenderia
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            
            if (!$karenderia || $dailyMenu->karenderia_id !== $karenderia->id) {
                return response()->json(['error' => 'Unauthorized: You can only delete your own daily menu items'], 403);
            }

            $dailyMenu->delete();

            return response()->json(['message' => 'Daily menu item removed successfully']);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete daily menu entry',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available karenderias for customers based on meal type and date
     */
    public function getAvailableForCustomers(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'date' => 'required|date',
                'meal_type' => 'required|in:breakfast,lunch,dinner',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'radius' => 'nullable|numeric|min:1|max:50' // km radius
            ]);

            $date = $validatedData['date'];
            $mealType = $validatedData['meal_type'];

            $query = DailyMenu::with(['karenderia', 'menuItem'])
                ->where('date', $date)
                ->where('meal_type', $mealType)
                ->available() // Only available items with quantity > 0
                ->whereHas('karenderia', function($q) {
                    $q->where(function ($statusQuery) {
                        $statusQuery->where('status', 'approved')
                            ->orWhere('status', 'active');
                    });
                });

            // If location provided, add distance filtering
            if (isset($validatedData['latitude']) && isset($validatedData['longitude'])) {
                $lat = $validatedData['latitude'];
                $lng = $validatedData['longitude'];
                $radius = $validatedData['radius'] ?? 10; // Default 10km radius

                $query->whereHas('karenderia', function($q) use ($lat, $lng, $radius) {
                    $q->whereRaw("
                        (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
                        cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
                        sin(radians(latitude)))) <= ?
                    ", [$lat, $lng, $lat, $radius]);
                });
            }

            $dailyMenus = $query->get();

            // Group by karenderia
            $karenderiaMenus = $dailyMenus->groupBy('karenderia_id')->map(function($items) {
                $karenderia = $items->first()->karenderia;
                return [
                    'karenderia' => $karenderia,
                    'menu_items' => $items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'menu_item' => $item->menuItem,
                            'quantity' => $item->quantity,
                            'special_price' => $item->special_price,
                            'notes' => $item->notes
                        ];
                    })
                ];
            })->values();

            return response()->json([
                'data' => $karenderiaMenus,
                'date' => $date,
                'meal_type' => $mealType,
                'total_karenderias' => $karenderiaMenus->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch available karenderias',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get menu items available for daily menu setup (for a specific karenderia)
     */
    public function getAvailableMenuItems(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Get the user's karenderia
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            
            if (!$karenderia) {
                return response()->json(['error' => 'No karenderia found for this user'], 403);
            }

            $menuItems = MenuItem::where('karenderia_id', $karenderia->id)
                ->where('is_available', true)
                ->select('id', 'name', 'description', 'price', 'category', 'image_url')
                ->orderBy('category')
                ->orderBy('name')
                ->get();

            return response()->json([
                'data' => $menuItems,
                'karenderia' => $karenderia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch menu items',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
