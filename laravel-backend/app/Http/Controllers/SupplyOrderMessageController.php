<?php

namespace App\Http\Controllers;

use App\Models\SupplyOrder;
use App\Models\SupplyOrderMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplyOrderMessageController extends Controller
{
    public function index(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $order = $this->resolveOrderForUser($orderId, $user->id, (string) $user->role);

        if (!$order) {
            return response()->json(['error' => 'Unauthorized to view messages for this order'], 403);
        }

        $messages = SupplyOrderMessage::with(['fromUser:id,name,role', 'toUser:id,name,role'])
            ->where('supply_order_id', $order->id)
            ->orderBy('created_at', 'asc')
            ->get();

        SupplyOrderMessage::where('supply_order_id', $order->id)
            ->where('to_user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return response()->json([
            'data' => $messages,
        ]);
    }

    public function store(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $order = $this->resolveOrderForUser($orderId, $user->id, (string) $user->role);

        if (!$order) {
            return response()->json(['error' => 'Unauthorized to send messages for this order'], 403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $recipientId = $user->id === $order->supplier_id
            ? $order->karenderia?->owner_id
            : $order->supplier_id;

        if (!$recipientId) {
            return response()->json(['error' => 'Unable to determine message recipient'], 422);
        }

        $message = SupplyOrderMessage::create([
            'supply_order_id' => $order->id,
            'from_user_id' => $user->id,
            'to_user_id' => $recipientId,
            'message' => trim($validated['message']),
            'is_read' => false,
        ]);

        $message->load(['fromUser:id,name,role', 'toUser:id,name,role']);

        return response()->json([
            'message' => 'Message sent',
            'data' => $message,
        ], 201);
    }

    public function destroy(Request $request, int $orderId): JsonResponse
    {
        $user = $request->user();
        $order = $this->resolveOrderForUser($orderId, $user->id, (string) $user->role);

        if (!$order) {
            return response()->json(['error' => 'Unauthorized to clear messages for this order'], 403);
        }

        SupplyOrderMessage::where('supply_order_id', $order->id)->delete();

        return response()->json([
            'message' => 'Conversation cleared',
        ]);
    }

    private function resolveOrderForUser(int $orderId, int $userId, string $role): ?SupplyOrder
    {
        $order = SupplyOrder::with('karenderia:id,owner_id')->find($orderId);

        if (!$order) {
            return null;
        }

        $canViewAsSupplier = $role === 'supplier' && $order->supplier_id === $userId;
        $canViewAsOwner = $role === 'karenderia_owner' && $order->karenderia && $order->karenderia->owner_id === $userId;

        if (!$canViewAsSupplier && !$canViewAsOwner) {
            return null;
        }

        return $order;
    }
}
