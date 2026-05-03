<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Build query based on user role
            $query = \App\Models\Order::with(['orderItems.menuItem', 'karenderia', 'customer']);
            
            if ($user) {
                if ($user->role === 'admin') {
                    // Admin can see all orders
                } elseif ($user->role === 'karenderia_owner') {
                    // Karenderia owner can see orders for their karenderias
                    $karenderiaIds = \App\Models\Karenderia::where('owner_id', $user->id)->pluck('id');
                    $query->whereIn('karenderia_id', $karenderiaIds);
                } else {
                    // Customers can see their own orders
                    $query->where('customer_id', $user->id);
                }
            } else {
                // Guest users can't see orders
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required'
                ], 401);
            }
            
            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }
            
            if ($request->has('karenderia_id')) {
                $query->where('karenderia_id', $request->karenderia_id);
            }
            
            // Order by most recent first
            $orders = $query->orderBy('created_at', 'desc')
                          ->paginate($request->get('per_page', 20));
            
            return response()->json([
                'success' => true,
                'data' => $orders->items(),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total()
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to fetch orders: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders'
            ], 500);
        }
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'karenderiaId' => 'required|exists:karenderias,id',
                'items' => 'required|array|min:1',
                'items.*.menuItemId' => 'required|string',
                'items.*.menuItemName' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unitPrice' => 'required|numeric|min:0',
                'items.*.subtotal' => 'required|numeric|min:0',
                'customerName' => 'nullable|string|max:255',
                'customerPhone' => 'nullable|string|max:20',
                'orderType' => 'required|in:dine-in,takeout,delivery',
                'subtotal' => 'required|numeric|min:0',
                'tax' => 'nullable|numeric|min:0',
                'discount' => 'nullable|numeric|min:0',
                'totalAmount' => 'required|numeric|min:0',
                'paymentMethod' => 'required|in:cash,card,gcash,online_payment',
                'notes' => 'nullable|string',
                'seasonalData' => 'nullable|array'
            ]);

            // Get the authenticated user (if any) - allow guest orders
            $user = $request->user();
            
            // ===== INVENTORY VALIDATION =====
            // Check if there's enough inventory for all items before creating order
            $inventoryService = app(\App\Services\InventoryService::class);
            $inventoryDeductions = [];
            
            foreach ($validatedData['items'] as $item) {
                $menuItem = \App\Models\MenuItem::where('id', $item['menuItemId'])
                    ->orWhere('name', $item['menuItemName'])
                    ->with('ingredients')
                    ->first();
                
                if ($menuItem && $menuItem->ingredients()->exists()) {
                    // Get all required ingredients for this menu item
                    foreach ($menuItem->ingredients as $ingredient) {
                        $totalNeeded = $ingredient->quantity_needed * $item['quantity'];
                        
                        // Check if sufficient inventory exists
                        if (!$inventoryService->checkStockAvailability($ingredient->inventory_id, $totalNeeded)) {
                            $inventory = Inventory::find($ingredient->inventory_id);
                            return response()->json([
                                'success' => false,
                                'message' => "Insufficient inventory for order",
                                'details' => [
                                    'item' => $item['menuItemName'],
                                    'required_ingredient' => $inventory->item_name,
                                    'needed' => $totalNeeded,
                                    'available' => $inventory->current_stock,
                                    'unit' => $inventory->unit
                                ]
                            ], 422);
                        }
                        
                        // Store deduction info for later
                        if (!isset($inventoryDeductions[$ingredient->inventory_id])) {
                            $inventoryDeductions[$ingredient->inventory_id] = 0;
                        }
                        $inventoryDeductions[$ingredient->inventory_id] += $totalNeeded;
                    }
                }
            }
            
            // ===== CREATE ORDER =====
            // Create the order
            $order = \App\Models\Order::create([
                'customer_id' => $user ? $user->id : null,
                'karenderia_id' => $validatedData['karenderiaId'],
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $validatedData['paymentMethod'],
                'subtotal' => $validatedData['subtotal'],
                'delivery_fee' => 0, // Set based on order type if needed
                'service_fee' => 0,
                'tax' => $validatedData['tax'] ?? 0,
                'total_amount' => $validatedData['totalAmount'],
                'total_cost' => 0, // Will be calculated based on menu items
                'delivery_address' => $validatedData['orderType'] === 'delivery' ? ($user && $user->address ? $user->address : null) : null,
                'special_instructions' => $validatedData['notes'] ?? null,
                'estimated_delivery_time' => $validatedData['orderType'] === 'delivery' ? now()->addMinutes(30) : null,
                'order_tracking' => [
                    'status' => 'pending',
                    'created_at' => now()->toISOString(),
                    'customer_name' => $validatedData['customerName'] ?? ($user->name ?? 'Guest'),
                    'customer_phone' => $validatedData['customerPhone'] ?? ($user->phone_number ?? null),
                    'order_type' => $validatedData['orderType'],
                    'seasonal_data' => $validatedData['seasonalData'] ?? null
                ]
            ]);

            // Create order items
            $totalCost = 0;
            foreach ($validatedData['items'] as $item) {
                // Try to find the menu item to get cost price
                $menuItem = \App\Models\MenuItem::where('id', $item['menuItemId'])
                    ->orWhere('name', $item['menuItemName'])
                    ->first();
                
                $unitCost = $menuItem ? $menuItem->cost_price : ($item['unitPrice'] * 0.6); // Default to 60% margin
                $itemTotalCost = $unitCost * $item['quantity'];
                $totalCost += $itemTotalCost;

                \App\Models\OrderItem::create([
                    'order_id' => $order->id,
                    'menu_item_id' => $menuItem ? $menuItem->id : null,
                    'menu_item_name' => $item['menuItemName'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unitPrice'],
                    'unit_cost' => $unitCost,
                    'total_price' => $item['subtotal'],
                    'total_cost' => $itemTotalCost,
                    'special_instructions' => null,
                    'preparation_time_minutes' => $menuItem ? $menuItem->preparation_time_minutes : 15
                ]);
            }

            // Update the order with total cost
            $order->update(['total_cost' => $totalCost]);
            
            // ===== DEDUCT INVENTORY =====
            // Now deduct the inventory for all items
            foreach ($inventoryDeductions as $inventoryId => $quantity) {
                $inventoryService->deductStock($inventoryId, $quantity, "Order #" . $order->id);
            }

            // Load the order with relationships
            $order->load(['orderItems', 'karenderia', 'customer']);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully and inventory deducted',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order' => $order,
                    'inventory_deducted' => count($inventoryDeductions) > 0
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent orders
     */
    public function getRecentOrders(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'orders' => []
        ]);
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Order details not implemented yet'
        ], 501);
    }

    /**
     * Update the specified order status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'orderStatus' => 'required|in:pending,preparing,ready,completed,cancelled',
                'preparedAt' => 'nullable|date',
                'completedAt' => 'nullable|date'
            ]);

            $order = \App\Models\Order::findOrFail($id);
            
            // Check if user has permission to update this order
            $user = $request->user();
            if ($user->role !== 'admin') {
                if ($user->role === 'karenderia_owner') {
                    $karenderiaIds = \App\Models\Karenderia::where('owner_id', $user->id)->pluck('id');
                    if (!$karenderiaIds->contains($order->karenderia_id)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Unauthorized to update this order'
                        ], 403);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized to update orders'
                    ], 403);
                }
            }

            $previousStatus = $order->status;
            $newStatus = $validatedData['orderStatus'];

            DB::transaction(function () use ($order, $validatedData, $previousStatus, $newStatus) {
                // Update order status
                $order->status = $newStatus;

                // Update order tracking with timestamps
                $tracking = $order->order_tracking ?? [];
                $tracking['status'] = $newStatus;
                $tracking['updated_at'] = now()->toISOString();

                if ($newStatus === 'preparing') {
                    $tracking['prepared_at'] = $validatedData['preparedAt'] ?? now()->toISOString();
                } elseif ($newStatus === 'completed') {
                    $tracking['completed_at'] = $validatedData['completedAt'] ?? now()->toISOString();
                    $order->payment_status = 'paid';
                }

                $order->order_tracking = $tracking;
                $order->save();

                // Apply one-time stock deduction only on first transition to completed.
                if ($newStatus === 'completed' && $previousStatus !== 'completed') {
                    $this->deductKitchenStockForCompletedOrder((int) $order->id, (int) $order->karenderia_id);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully',
                'data' => $order->fresh(['orderItems.menuItem', 'karenderia', 'customer'])
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found'
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Order status update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status'
            ], 500);
        }
    }

    private function deductKitchenStockForCompletedOrder(int $orderId, int $karenderiaId): void
    {
        $orderItems = DB::table('order_items')
            ->where('order_id', $orderId)
            ->get(['menu_item_id', 'quantity']);

        $menuItemNames = DB::table('order_items')
            ->join('menu_items', 'menu_items.id', '=', 'order_items.menu_item_id')
            ->where('order_items.order_id', $orderId)
            ->pluck('menu_items.name', 'order_items.menu_item_id');

        foreach ($orderItems as $orderItem) {
            $orderQuantity = (float) $orderItem->quantity;
            if ($orderQuantity <= 0) {
                continue;
            }

            $deductedViaIngredients = false;

            if (!empty($orderItem->menu_item_id)) {
                $menuItem = DB::table('menu_items')
                    ->where('id', $orderItem->menu_item_id)
                    ->first(['ingredients']);

                if ($menuItem && !empty($menuItem->ingredients)) {
                    $ingredients = is_array($menuItem->ingredients)
                        ? $menuItem->ingredients
                        : json_decode((string) $menuItem->ingredients, true);

                    if (is_array($ingredients) && count($ingredients) > 0) {
                        foreach ($ingredients as $ingredient) {
                            $ingredientName = $this->resolveIngredientName($ingredient);
                            if (!$ingredientName) {
                                continue;
                            }

                            $perOrderUsage = $this->resolveIngredientUsage($ingredient);
                            $totalUsage = $perOrderUsage * $orderQuantity;
                            $this->decrementInventoryItem($karenderiaId, $ingredientName, $totalUsage);
                            $deductedViaIngredients = true;
                        }
                    }
                }
            }

            // Fallback: use menu item name directly when no structured ingredient data exists.
            $fallbackName = $orderItem->menu_item_id ? ($menuItemNames[$orderItem->menu_item_id] ?? null) : null;
            if (!$deductedViaIngredients && is_string($fallbackName) && trim($fallbackName) !== '') {
                $this->decrementInventoryItem($karenderiaId, trim($fallbackName), $orderQuantity);
            }
        }
    }

    private function resolveIngredientName($ingredient): ?string
    {
        if (is_string($ingredient)) {
            return trim($ingredient) !== '' ? trim($ingredient) : null;
        }

        if (is_array($ingredient)) {
            $name = $ingredient['ingredientName'] ?? $ingredient['name'] ?? $ingredient['ingredient'] ?? $ingredient['item_name'] ?? null;
            if (is_string($name) && trim($name) !== '') {
                return trim($name);
            }
        }

        return null;
    }

    private function resolveIngredientUsage($ingredient): float
    {
        if (!is_array($ingredient)) {
            return 1.0;
        }

        $usage = $ingredient['quantity'] ?? $ingredient['amount'] ?? $ingredient['qty'] ?? 1;
        $usage = (float) $usage;

        return $usage > 0 ? $usage : 1.0;
    }

    private function decrementInventoryItem(int $karenderiaId, string $itemName, float $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $normalizedName = mb_strtolower(trim($itemName));

        $inventoryItem = Inventory::where('karenderia_id', $karenderiaId)
            ->whereRaw('LOWER(item_name) = ?', [$normalizedName])
            ->first();

        if (!$inventoryItem) {
            return;
        }

        $current = (float) $inventoryItem->current_stock;
        $inventoryItem->current_stock = max(0, $current - $amount);
        $inventoryItem->save();
    }
}
