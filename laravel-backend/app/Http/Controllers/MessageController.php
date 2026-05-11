<?php

namespace App\Http\Controllers;

use App\Models\IngredientRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Send a message between owner and supplier
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'ingredient_request_id' => 'required|exists:ingredient_requests,id',
            'message' => 'required|string|max:1000',
            'type' => 'nullable|in:text,call_request,system',
            'call_phone_number' => 'nullable|string|max:20',
        ]);

        $ingredientRequest = IngredientRequest::findOrFail($validated['ingredient_request_id']);

        // Verify user is involved in this request
        $isOwner = $ingredientRequest->karenderia->owner_id === $user->id;
        $isSupplier = $ingredientRequest->accepted_supplier_id === $user->id || 
                      $ingredientRequest->quotes()->where('supplier_id', $user->id)->exists();

        if (!$isOwner && !$isSupplier) {
            return response()->json(['message' => 'You are not involved in this request'], 403);
        }

        $message = Message::create([
            'from_user_id' => $user->id,
            'to_user_id' => $validated['to_user_id'],
            'ingredient_request_id' => $validated['ingredient_request_id'],
            'message' => $validated['message'],
            'type' => $validated['type'] ?? 'text',
            'call_phone_number' => $validated['call_phone_number'],
        ]);

        $message->load(['fromUser:id,name,role', 'toUser:id,name,role']);

        return response()->json([
            'message' => 'Message sent',
            'data' => $message,
        ], 201);
    }

    /**
     * Get conversation between two users for a specific request
     */
    public function getConversation(Request $request, IngredientRequest $ingredientRequest): JsonResponse
    {
        $user = Auth::user();
        $otherUserId = $request->query('with');

        if (!$otherUserId) {
            return response()->json(['message' => 'Required parameter: with'], 400);
        }

        // Verify user is involved
        $isOwner = $ingredientRequest->karenderia->owner_id === $user->id;
        $isSupplier = $ingredientRequest->accepted_supplier_id === $user->id || 
                      $ingredientRequest->quotes()->where('supplier_id', $user->id)->exists();

        if (!$isOwner && !$isSupplier) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $messages = Message::where('ingredient_request_id', $ingredientRequest->id)
            ->where(function ($query) use ($user, $otherUserId) {
                $query->where('from_user_id', $user->id)
                    ->where('to_user_id', $otherUserId)
                    ->orWhere('from_user_id', $otherUserId)
                    ->where('to_user_id', $user->id);
            })
            ->with(['fromUser:id,name,role', 'toUser:id,name,role'])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        // Mark messages as read
        Message::where('ingredient_request_id', $ingredientRequest->id)
            ->where('from_user_id', $otherUserId)
            ->where('to_user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json([
            'data' => $messages,
        ]);
    }

    /**
     * Get all conversations for a user (grouped by request)
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = Auth::user();

        $conversations = Message::where('from_user_id', $user->id)
            ->orWhere('to_user_id', $user->id)
            ->with(['ingredientRequest', 'fromUser:id,name,role', 'toUser:id,name,role'])
            ->groupBy('ingredient_request_id')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $conversations,
        ]);
    }

    /**
     * Get unread message count
     */
    public function unreadCount(): JsonResponse
    {
        $user = Auth::user();

        $count = Message::where('to_user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    /**
     * Request a phone call (send message with call_request type)
     */
    public function requestCall(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'ingredient_request_id' => 'required|exists:ingredient_requests,id',
            'call_phone_number' => 'required|string|max:20',
        ]);

        $ingredientRequest = IngredientRequest::findOrFail($validated['ingredient_request_id']);

        $message = Message::create([
            'from_user_id' => $user->id,
            'to_user_id' => $validated['to_user_id'],
            'ingredient_request_id' => $ingredientRequest->id,
            'type' => 'call_request',
            'call_phone_number' => $validated['call_phone_number'],
            'message' => $user->name . ' is requesting a call',
            'call_status' => 'pending',
        ]);

        $message->load(['fromUser:id,name,phone_number', 'toUser:id,name,phone_number']);

        return response()->json([
            'message' => 'Call request sent',
            'data' => $message,
        ], 201);
    }
}
