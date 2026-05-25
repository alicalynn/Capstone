<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Karenderia;
use App\Models\KarenderiaSupplierSuki;
use App\Models\SupplierInventoryItem;
use App\Models\SupplyOrder;
use App\Models\User;
use App\Notifications\OwnerConfirmedDeliveryNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierWorkflowController extends Controller
{
    public function marketplace(Request $request): JsonResponse
    {
        $user = $request->user();
        $sukiOnly = filter_var($request->query('suki_only', false), FILTER_VALIDATE_BOOLEAN);

        $ownerSukiSupplierIds = collect();
        if ($user && $user->role === 'karenderia_owner') {
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            if ($karenderia) {
                $ownerSukiSupplierIds = KarenderiaSupplierSuki::where('karenderia_id', $karenderia->id)
                    ->pluck('supplier_id');
            }
        }

        $query = SupplierInventoryItem::query()
            ->with(['supplier:id,name,email'])
            ->where('is_active', true)
            ->where('available_stock', '>', 0)
            ->orderBy('item_name');

        if ($sukiOnly && $ownerSukiSupplierIds->count() > 0) {
            $query->whereIn('supplier_id', $ownerSukiSupplierIds->all());
        }

        if ($sukiOnly && $ownerSukiSupplierIds->count() === 0) {
            return response()->json([
                'data' => [],
            ]);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $listings = $query->get()->map(function (SupplierInventoryItem $item) use ($ownerSukiSupplierIds) {
            $item->is_suki = $ownerSukiSupplierIds->contains($item->supplier_id);
            return $item;
        });

        return response()->json([
            'data' => $listings,
        ]);
    }

    public function sukiSuppliers(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'karenderia_owner') {
            return response()->json(['error' => 'Only karenderia owners can manage Suki suppliers'], 403);
        }

        $karenderia = Karenderia::where('owner_id', $user->id)->first();
        if (!$karenderia) {
            return response()->json(['error' => 'No karenderia found for this account'], 403);
        }

        $supplierIds = KarenderiaSupplierSuki::where('karenderia_id', $karenderia->id)
            ->pluck('supplier_id');

        if ($supplierIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $suppliers = User::query()
            ->whereIn('id', $supplierIds)
            ->where('role', 'supplier')
            ->select(['id', 'name', 'email'])
            ->get()
            ->map(function (User $supplier) {
                $supplier->listing_count = SupplierInventoryItem::where('supplier_id', $supplier->id)
                    ->where('is_active', true)
                    ->count();
                return $supplier;
            });

        return response()->json([
            'data' => $suppliers,
        ]);
    }

    public function markSukiSupplier(Request $request, int $supplierId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'karenderia_owner') {
            return response()->json(['error' => 'Only karenderia owners can mark Suki suppliers'], 403);
        }

        $karenderia = Karenderia::where('owner_id', $user->id)->first();
        if (!$karenderia) {
            return response()->json(['error' => 'No karenderia found for this account'], 403);
        }

        $supplier = User::where('id', $supplierId)->where('role', 'supplier')->first();
        if (!$supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }

        KarenderiaSupplierSuki::firstOrCreate([
            'karenderia_id' => $karenderia->id,
            'supplier_id' => $supplier->id,
        ]);

        return response()->json([
            'message' => 'Supplier added to Suki list',
            'data' => [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
            ],
        ]);
    }

    public function unmarkSukiSupplier(Request $request, int $supplierId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'karenderia_owner') {
            return response()->json(['error' => 'Only karenderia owners can remove Suki suppliers'], 403);
        }

        $karenderia = Karenderia::where('owner_id', $user->id)->first();
        if (!$karenderia) {
            return response()->json(['error' => 'No karenderia found for this account'], 403);
        }

        KarenderiaSupplierSuki::where('karenderia_id', $karenderia->id)
            ->where('supplier_id', $supplierId)
            ->delete();

        return response()->json([
            'message' => 'Supplier removed from Suki list',
        ]);
    }

    public function supplierListings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'supplier') {
                return response()->json(['error' => 'Only supplier accounts can view supplier listings'], 403);
            }

            $listings = SupplierInventoryItem::where('supplier_id', $user->id)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'data' => $listings,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading supplier listings:', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to load supplier listings',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function createSupplierListing(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'supplier') {
            return response()->json(['error' => 'Only supplier accounts can create listings'], 403);
        }

        $validated = $request->validate([
            'item_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'price_per_unit' => 'required|numeric|min:0.01',
            'available_stock' => 'required|numeric|min:0',
            'minimum_order_quantity' => 'nullable|numeric|min:0.001',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['supplier_id'] = $user->id;
        $validated['minimum_order_quantity'] = $validated['minimum_order_quantity'] ?? 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $listing = SupplierInventoryItem::create($validated);

        return response()->json([
            'message' => 'Supplier listing created successfully',
            'data' => $listing,
        ], 201);
    }

    public function updateSupplierListing(Request $request, int $listingId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'supplier') {
            return response()->json(['error' => 'Only supplier accounts can update listings'], 403);
        }

        $listing = SupplierInventoryItem::where('supplier_id', $user->id)->findOrFail($listingId);

        $validated = $request->validate([
            'item_name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|string|max:100',
            'unit' => 'sometimes|string|max:50',
            'price_per_unit' => 'sometimes|numeric|min:0.01',
            'available_stock' => 'sometimes|numeric|min:0',
            'minimum_order_quantity' => 'sometimes|numeric|min:0.001',
            'is_active' => 'sometimes|boolean',
        ]);

        $listing->update($validated);

        return response()->json([
            'message' => 'Supplier listing updated successfully',
            'data' => $listing->fresh(),
        ]);
    }

    public function createSupplyOrder(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'karenderia_owner') {
            return response()->json(['error' => 'Only karenderia owners can place supply orders'], 403);
        }

        $karenderia = Karenderia::where('owner_id', $user->id)->first();
        if (!$karenderia) {
            return response()->json(['error' => 'No karenderia found for this account'], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.supplier_inventory_item_id' => 'required|exists:supplier_inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'payment_method' => 'nullable|in:cod,paymaya_sandbox,paypal_sandbox',
            'notes' => 'nullable|string',
            'delivery_date' => 'nullable|date',
        ]);

        $itemIds = collect($validated['items'])->pluck('supplier_inventory_item_id')->unique()->values();
        $supplierItems = SupplierInventoryItem::whereIn('id', $itemIds)->where('is_active', true)->get()->keyBy('id');

        if ($supplierItems->count() !== $itemIds->count()) {
            return response()->json(['error' => 'Some supplier items are unavailable'], 422);
        }

        $supplierIds = $supplierItems->pluck('supplier_id')->unique();
        if ($supplierIds->count() !== 1) {
            return response()->json(['error' => 'Order items must come from a single supplier'], 422);
        }

        foreach ($validated['items'] as $orderItem) {
            $supplierItem = $supplierItems[$orderItem['supplier_inventory_item_id']];

            if ((float) $orderItem['quantity'] < (float) $supplierItem->minimum_order_quantity) {
                return response()->json([
                    'error' => "Quantity for {$supplierItem->item_name} is below the minimum order quantity",
                ], 422);
            }

            if ((float) $orderItem['quantity'] > (float) $supplierItem->available_stock) {
                return response()->json([
                    'error' => "Insufficient stock for {$supplierItem->item_name}",
                ], 422);
            }
        }

        $order = DB::transaction(function () use ($validated, $karenderia, $supplierItems, $supplierIds) {
            $order = SupplyOrder::create([
                'karenderia_id' => $karenderia->id,
                'supplier_id' => $supplierIds->first(),
                'status' => 'pending',
                'payment_status' => 'pending',
                'payment_method' => $validated['payment_method'] ?? 'cod',
                'total_amount' => 0,
                'notes' => $validated['notes'] ?? null,
                'delivery_date' => $validated['delivery_date'] ?? null,
            ]);

            $totalAmount = 0;

            foreach ($validated['items'] as $orderItem) {
                $supplierItem = $supplierItems[$orderItem['supplier_inventory_item_id']];
                $quantity = (float) $orderItem['quantity'];
                $unitPrice = (float) $supplierItem->price_per_unit;
                $lineTotal = $quantity * $unitPrice;

                $order->items()->create([
                    'supplier_inventory_item_id' => $supplierItem->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);

                $supplierItem->decrement('available_stock', $quantity);
                $totalAmount += $lineTotal;
            }

            $order->update([
                'total_amount' => $totalAmount,
            ]);

            return $order;
        });

        return response()->json([
            'message' => 'Supply order placed successfully',
            'data' => $order->load([
                'supplier:id,name,email',
                'karenderia:id,business_name,name',
                'items.supplierItem:id,item_name,unit',
            ]),
        ], 201);
    }

    public function ownerOrders(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'karenderia_owner') {
            return response()->json(['error' => 'Only karenderia owners can view owner orders'], 403);
        }

        $karenderia = Karenderia::where('owner_id', $user->id)->first();
        if (!$karenderia) {
            return response()->json(['error' => 'No karenderia found for this account'], 403);
        }

        $orders = SupplyOrder::with([
            'supplier:id,name,email',
            'items.supplierItem:id,item_name,unit',
        ])
            ->where('karenderia_id', $karenderia->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    public function supplierOrders(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'supplier') {
                return response()->json(['error' => 'Only supplier accounts can view supplier orders'], 403);
            }

            $orders = SupplyOrder::with([
                'karenderia:id,business_name,name',
                'items.supplierItem:id,item_name,unit',
            ])
                ->where('supplier_id', $user->id)
                ->orderByDesc('created_at')
                ->get();

            return response()->json([
                'data' => $orders,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading supplier orders:', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Failed to load supplier orders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed order information with status timeline
     */
    public function getOrderDetail(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $order = SupplyOrder::with([
            'supplier:id,name,email',
            'karenderia:id,business_name,name,address',
            'items.supplierItem:id,item_name,description,unit,category',
        ])->findOrFail($orderId);

        // Permission check
        $canView = false;
        if ($user->role === 'supplier' && $order->supplier_id === $user->id) {
            $canView = true;
        }
        if ($user->role === 'karenderia_owner') {
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            if ($karenderia && $karenderia->id === $order->karenderia_id) {
                $canView = true;
            }
        }

        if (!$canView) {
            return response()->json(['error' => 'Unauthorized to view this order'], 403);
        }

        return response()->json([
            'data' => [
                'order' => $order,
                'timeline' => $order->getStatusTimeline(),
                'next_possible_statuses' => $order->getNextPossibleStatuses(),
                'is_terminal' => $order->isTerminal(),
                'can_retry' => $order->canBeRetried(),
            ]
        ]);
    }

    public function updateOrderStatus(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $order = SupplyOrder::with('items.supplierItem')->findOrFail($orderId);

        // Permission checks
        $canManageAsSupplier = $user->role === 'supplier' && $order->supplier_id === $user->id;
        $canManageAsOwner = false;

        if ($user->role === 'karenderia_owner') {
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            $canManageAsOwner = $karenderia && $karenderia->id === $order->karenderia_id;
        }

        if (!$canManageAsSupplier && !$canManageAsOwner) {
            return response()->json(['error' => 'Unauthorized to update this order'], 403);
        }

        // Validate request
        $validated = $request->validate([
            'status' => 'required|in:confirmed,payment_confirmed,preparing,shipped,in_transit,out_for_delivery,delivering,delivered,delivery_failed,cancelled',
            'delivery_method' => 'sometimes|in:pickup,delivery,courier',
            'delivery_address' => 'sometimes|string|max:500',
            'delivery_coordinates' => 'sometimes|json',
            'delivered_by_name' => 'sometimes|string|max:255',
            'delivery_notes' => 'sometimes|string|max:1000',
            'delivery_signature_url' => 'sometimes|url',
            'photo_proof_urls' => 'sometimes|json',
            'failed_reason' => 'sometimes|string|max:500',
            'payment_method' => 'sometimes|in:cod,paymaya_sandbox,paypal_sandbox,gcash,bank_transfer,onsite,credit_card',
            'payment_reference' => 'sometimes|string|max:255',
        ]);

        $newStatus = $validated['status'];
        $previousStatus = $order->status;

        // Check if order is in terminal state
        if ($order->isTerminal()) {
            return response()->json([
                'error' => "Cannot update order in terminal state ({$previousStatus})"
            ], 422);
        }

        // Validate status transition
        $allowedNextStatuses = $order->getNextPossibleStatuses();
        if (!in_array($newStatus, $allowedNextStatuses)) {
            return response()->json([
                'error' => "Invalid status transition from {$previousStatus} to {$newStatus}",
                'allowed_statuses' => $allowedNextStatuses
            ], 422);
        }

        // Permission checks for status transitions
        // Only supplier can update status forward, except owner can confirm delivery
        if ($canManageAsOwner) {
            // Karenderia owners can only mark delivered or cancel orders
            if ($newStatus !== 'cancelled' && $newStatus !== 'delivered') {
                return response()->json([
                    'error' => 'Karenderia owners can only mark orders as delivered or cancel them'
                ], 403);
            }
            // Owner can only mark as delivered when status is 'delivering'
            if ($newStatus === 'delivered' && $previousStatus !== 'delivering') {
                return response()->json([
                    'error' => 'Can only mark as delivered when order is in delivering status',
                    'current_status' => $previousStatus
                ], 422);
            }
        } elseif ($canManageAsSupplier) {
            // Supplier cannot mark as delivered until owner has confirmed
            if ($newStatus === 'delivered' && !$order->owner_confirmed_delivery) {
                return response()->json([
                    'error' => 'Cannot mark as delivered until owner confirms delivery',
                    'current_status' => $previousStatus
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($order, $newStatus, $previousStatus, $validated, $user, $canManageAsOwner) {
                $updateData = [
                    'status' => $newStatus,
                ];

                // Record status change in history
                $order->recordStatusChange($newStatus, $validated['failed_reason'] ?? null);

                // Handle specific status transitions
                switch ($newStatus) {
                    case 'confirmed':
                        $updateData['confirmed_at'] = now();
                        break;

                    case 'payment_confirmed':
                        $updateData['payment_status'] = 'confirmed';
                        $updateData['payment_date'] = now();
                        if (isset($validated['payment_method'])) {
                            $updateData['payment_method'] = $validated['payment_method'];
                        }
                        if (isset($validated['payment_reference'])) {
                            $updateData['payment_reference'] = $validated['payment_reference'];
                        }
                        break;

                    case 'preparing':
                        if (isset($validated['delivery_method'])) {
                            $updateData['delivery_method'] = $validated['delivery_method'];
                        }
                        if (isset($validated['delivery_address'])) {
                            $updateData['delivery_address'] = $validated['delivery_address'];
                        }
                        if (isset($validated['delivery_coordinates'])) {
                            $updateData['delivery_coordinates'] = json_decode($validated['delivery_coordinates'], true);
                        }
                        break;

                    case 'shipped':
                        $updateData['shipped_at'] = now();
                        break;

                    case 'out_for_delivery':
                        $updateData['out_for_delivery_at'] = now();
                        if (isset($validated['delivered_by_name'])) {
                            $updateData['delivered_by_name'] = $validated['delivered_by_name'];
                        }
                        break;

                    case 'delivering':
                        // Order is currently being delivered
                        if (isset($validated['delivered_by_name'])) {
                            $updateData['delivered_by_name'] = $validated['delivered_by_name'];
                        }
                        break;

                    case 'delivered':
                        // *** CRITICAL: Only sync kitchen stock when order is DELIVERED ***
                        $updateData['delivered_at'] = now();
                        
                        // If owner is confirming delivery, set the confirmation flag
                        if ($canManageAsOwner) {
                            $updateData['owner_confirmed_delivery'] = true;
                            $updateData['owner_confirmed_delivery_at'] = now();
                        }
                        
                        if (isset($validated['delivery_notes'])) {
                            $updateData['delivery_notes'] = $validated['delivery_notes'];
                        }
                        if (isset($validated['delivery_signature_url'])) {
                            $updateData['delivery_signature_url'] = $validated['delivery_signature_url'];
                        }
                        if (isset($validated['photo_proof_urls'])) {
                            $updateData['photo_proof_urls'] = json_decode($validated['photo_proof_urls'], true);
                        }

                        // Sync kitchen stock ONLY on delivery
                        $this->syncKitchenStockFromSupplyOrder($order, true);
                        break;

                    case 'delivery_failed':
                        $updateData['failed_reason'] = $validated['failed_reason'] ?? 'Unknown reason';
                        $updateData['retry_count'] = $order->retry_count + 1;
                        break;

                    case 'cancelled':
                        // Restore supplier's available stock
                        foreach ($order->items as $item) {
                            if ($item->supplierItem) {
                                $item->supplierItem->increment('available_stock', (float) $item->quantity);
                            }
                        }

                        // Rollback kitchen stock if it was already synced on delivery
                        if ($previousStatus === 'delivered') {
                            $this->syncKitchenStockFromSupplyOrder($order, false);
                        }
                        break;
                }

                $order->update($updateData);

                // Log the status update
                Log::info("Supply order {$order->id} status updated", [
                    'from_status' => $previousStatus,
                    'to_status' => $newStatus,
                    'user_id' => $user->id,
                    'user_role' => $user->role,
                    'timestamp' => now(),
                ]);
            });

            // Return updated order with timeline
            // Refresh order from database
            $updatedOrder = SupplyOrder::find($orderId);

            // Log delivery confirmation notification for supplier (non-blocking)
            if ($newStatus === 'delivered' && $canManageAsOwner) {
                Log::info("Delivery confirmed by karenderia owner", [
                    'order_id' => $updatedOrder->id,
                    'owner_id' => $user->id,
                    'owner_email' => $user->email,
                    'supplier_id' => $updatedOrder->supplier_id,
                ]);
                
                // Send notification to supplier that owner has confirmed delivery
                if ($updatedOrder->supplier) {
                    $updatedOrder->supplier->notify(new OwnerConfirmedDeliveryNotification($updatedOrder));
                }
            }

            return response()->json([
                'message' => 'Order status updated successfully',
                'data' => [
                    'order' => $updatedOrder,
                    'status' => $updatedOrder->status,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Failed to update supply order status", [
                'order_id' => $orderId,
                'requested_status' => $newStatus,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update order status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function syncKitchenStockFromSupplyOrder(SupplyOrder $order, bool $increase): void
    {
        foreach ($order->items as $item) {
            $supplierItem = $item->supplierItem;
            if (!$supplierItem) {
                continue;
            }

            $inventoryItem = Inventory::where('karenderia_id', $order->karenderia_id)
                ->whereRaw('LOWER(item_name) = ?', [mb_strtolower(trim((string) $supplierItem->item_name))])
                ->first();

            if (!$inventoryItem) {
                $inventoryItem = new Inventory();
                $inventoryItem->karenderia_id = $order->karenderia_id;
                $inventoryItem->item_name = $supplierItem->item_name;
                $inventoryItem->description = $supplierItem->description;
                $inventoryItem->category = $supplierItem->category ?: 'Supplies';
                $inventoryItem->unit = $supplierItem->unit;
                $inventoryItem->minimum_stock = 0;
                $inventoryItem->unit_cost = (float) $item->unit_price;
                $inventoryItem->current_stock = 0;
                $inventoryItem->supplier = 'Supplier Marketplace';
            }

            $stockDelta = (float) $item->quantity;
            $currentStock = (float) $inventoryItem->current_stock;
            $inventoryItem->current_stock = $increase
                ? ($currentStock + $stockDelta)
                : max(0, $currentStock - $stockDelta);

            // Keep recent purchase pricing on sync operations.
            $inventoryItem->unit_cost = (float) $item->unit_price;
            $inventoryItem->last_restocked = now();
            $inventoryItem->save();
        }
    }

    private function notifySupplierOfDeliveryConfirmation(SupplyOrder $order, User $ownerUser): void
    {
        try {
            // Get the supplier
            $supplier = User::find($order->supplier_id);
            if (!$supplier) {
                return;
            }

            // Get karenderia info for the notification message
            $karenderia = Karenderia::find($order->karenderia_id);
            $karenderiaName = $karenderia?->business_name ?: $karenderia?->name ?: 'Karenderia';

            // Create notification message - this will appear as a system message in the order conversation
            $notificationMessage = "[SYSTEM] Order #$order->id delivery confirmed by $karenderiaName on " . now()->format('M d, Y H:i A');

            // Log the notification (can be extended to send actual notifications later)
            Log::info('Delivery Confirmation Notification', [
                'order_id' => $order->id,
                'supplier_id' => $supplier->id,
                'karenderia_name' => $karenderiaName,
                'confirmed_by' => $ownerUser->email,
                'timestamp' => now(),
            ]);

            // In a real app, you could also:
            // 1. Send email notification to supplier
            // 2. Create database notification record
            // 3. Trigger real-time notification via websocket
            // For now, the system message in the conversation serves as notification
        } catch (\Exception $e) {
            Log::error('Error notifying supplier of delivery:', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
