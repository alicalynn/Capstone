<?php

namespace App\Http\Controllers;

use App\Models\Karenderia;
use App\Models\SupplierInventoryItem;
use App\Models\SupplyOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierWorkflowController extends Controller
{
    public function marketplace(Request $request): JsonResponse
    {
        $query = SupplierInventoryItem::query()
            ->with(['supplier:id,name,email'])
            ->where('is_active', true)
            ->where('available_stock', '>', 0)
            ->orderBy('item_name');

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

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function supplierListings(Request $request): JsonResponse
    {
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
    }

    public function updateOrderStatus(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();

        $order = SupplyOrder::with('items.supplierItem')->findOrFail($orderId);

        $canManageAsSupplier = $user->role === 'supplier' && $order->supplier_id === $user->id;
        $isOwnerCancellingOwnOrder = false;

        if ($user->role === 'karenderia_owner') {
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            $isOwnerCancellingOwnOrder = $karenderia && $karenderia->id === $order->karenderia_id;
        }

        if (!$canManageAsSupplier && !$isOwnerCancellingOwnOrder) {
            return response()->json(['error' => 'Unauthorized to update this order'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,delivered,cancelled',
        ]);

        if ($isOwnerCancellingOwnOrder && $validated['status'] !== 'cancelled') {
            return response()->json(['error' => 'Karenderia owners can only cancel orders'], 422);
        }

        if ($order->status === 'cancelled' || $order->status === 'delivered') {
            return response()->json(['error' => 'This order can no longer be updated'], 422);
        }

        DB::transaction(function () use ($order, $validated) {
            if ($validated['status'] === 'cancelled' && $order->status !== 'cancelled') {
                foreach ($order->items as $item) {
                    if ($item->supplierItem) {
                        $item->supplierItem->increment('available_stock', (float) $item->quantity);
                    }
                }
            }

            $order->update([
                'status' => $validated['status'],
            ]);
        });

        return response()->json([
            'message' => 'Order status updated successfully',
            'data' => $order->fresh()->load([
                'supplier:id,name,email',
                'karenderia:id,business_name,name',
                'items.supplierItem:id,item_name,unit',
            ]),
        ]);
    }
}
