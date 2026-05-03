<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MenuItemIngredient;
use App\Models\MenuItem;
use App\Models\Karenderia;
use App\Models\Inventory;

class MenuItemIngredientController extends Controller
{
    /**
     * Get ingredients for a specific menu item
     */
    public function index(Request $request, $menuItemId)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Verify the user owns this menu item
            $menuItem = MenuItem::findOrFail($menuItemId);
            $karenderia = Karenderia::where('owner_id', $user->id)
                ->where('id', $menuItem->karenderia_id)
                ->first();
            
            if (!$karenderia) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $ingredients = MenuItemIngredient::with(['inventory'])
                ->where('menu_item_id', $menuItemId)
                ->get();

            return response()->json([
                'data' => $ingredients,
                'menu_item' => $menuItem
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch ingredients',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add an ingredient to a menu item
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $validatedData = $request->validate([
                'menu_item_id' => 'required|exists:menu_items,id',
                'inventory_id' => 'required|exists:inventory,id',
                'quantity_needed' => 'required|numeric|min:0.001'
            ]);

            // Verify the user owns this menu item
            $menuItem = MenuItem::findOrFail($validatedData['menu_item_id']);
            $karenderia = Karenderia::where('owner_id', $user->id)
                ->where('id', $menuItem->karenderia_id)
                ->first();
            
            if (!$karenderia) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Verify the inventory belongs to the same karenderia
            $inventory = Inventory::findOrFail($validatedData['inventory_id']);
            if ($inventory->karenderia_id !== $karenderia->id) {
                return response()->json(['error' => 'Inventory does not belong to your karenderia'], 403);
            }

            // Check if ingredient already exists
            $existingIngredient = MenuItemIngredient::where('menu_item_id', $validatedData['menu_item_id'])
                ->where('inventory_id', $validatedData['inventory_id'])
                ->first();

            if ($existingIngredient) {
                return response()->json(['error' => 'This ingredient is already added to this menu item'], 422);
            }

            $ingredient = MenuItemIngredient::create($validatedData);
            $ingredient->load(['inventory']);

            return response()->json([
                'message' => 'Ingredient added to menu item successfully',
                'data' => $ingredient
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to add ingredient',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update ingredient quantity for a menu item
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $ingredient = MenuItemIngredient::findOrFail($id);
            
            // Verify the user owns this menu item
            $menuItem = $ingredient->menuItem;
            $karenderia = Karenderia::where('owner_id', $user->id)
                ->where('id', $menuItem->karenderia_id)
                ->first();
            
            if (!$karenderia) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $validatedData = $request->validate([
                'quantity_needed' => 'required|numeric|min:0.001'
            ]);

            $ingredient->update($validatedData);
            $ingredient->load(['inventory']);

            return response()->json([
                'message' => 'Ingredient quantity updated successfully',
                'data' => $ingredient
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update ingredient',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove an ingredient from a menu item
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            $ingredient = MenuItemIngredient::findOrFail($id);
            
            // Verify the user owns this menu item
            $menuItem = $ingredient->menuItem;
            $karenderia = Karenderia::where('owner_id', $user->id)
                ->where('id', $menuItem->karenderia_id)
                ->first();
            
            if (!$karenderia) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $ingredient->delete();

            return response()->json([
                'message' => 'Ingredient removed from menu item successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to remove ingredient',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available inventory items for adding as ingredients
     */
    public function availableInventory(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Get user's karenderia
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            
            if (!$karenderia) {
                return response()->json(['error' => 'No karenderia found'], 403);
            }

            // Get all available inventory items
            $inventoryItems = Inventory::where('karenderia_id', $karenderia->id)
                ->where('status', '!=', 'out_of_stock')
                ->select(['id', 'item_name', 'category', 'unit', 'current_stock'])
                ->orderBy('category')
                ->orderBy('item_name')
                ->get();

            return response()->json([
                'data' => $inventoryItems
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch available inventory',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
