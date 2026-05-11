<?php

namespace App\Http\Controllers;

use App\Models\IngredientRequest;
use App\Models\SupplierQuote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class SupplierQuoteController extends Controller
{
    /**
     * SUPPLIER: Submit a quote for an ingredient request
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== 'supplier') {
            return response()->json(['message' => 'Only suppliers can submit quotes'], 403);
        }

        $validated = $request->validate([
            'ingredient_request_id' => 'required|exists:ingredient_requests,id',
            'price_per_unit' => 'required|numeric|min:0.01',
            'available_quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string',
            'delivery_date' => 'nullable|date|after:today',
            'delivery_method' => 'nullable|in:pickup,delivery',
            'notes' => 'nullable|string|max:500',
        ]);

        $ingredientRequest = IngredientRequest::findOrFail($validated['ingredient_request_id']);

        // Check if request is still open
        if ($ingredientRequest->status !== 'open') {
            return response()->json(['message' => 'This request is no longer open'], 400);
        }

        // Check if supplier already quoted for this request
        $existingQuote = SupplierQuote::where('ingredient_request_id', $ingredientRequest->id)
            ->where('supplier_id', $user->id)
            ->first();

        if ($existingQuote) {
            return response()->json(['message' => 'You have already quoted for this request'], 400);
        }

        // Calculate total price
        $totalPrice = $validated['price_per_unit'] * $validated['available_quantity'];

        $quote = SupplierQuote::create([
            'ingredient_request_id' => $ingredientRequest->id,
            'supplier_id' => $user->id,
            'price_per_unit' => $validated['price_per_unit'],
            'total_price' => $totalPrice,
            'available_quantity' => $validated['available_quantity'],
            'unit' => $validated['unit'],
            'delivery_date' => $validated['delivery_date'],
            'delivery_method' => $validated['delivery_method'],
            'notes' => $validated['notes'],
            'status' => 'pending',
        ]);

        // Notify the karenderia owner
        // TODO: Send notification to owner

        return response()->json([
            'message' => 'Quote submitted successfully',
            'data' => $quote,
        ], 201);
    }

    /**
     * OWNER: Accept a supplier's quote
     */
    public function accept(Request $request, SupplierQuote $quote): JsonResponse
    {
        $user = Auth::user();

        $ingredientRequest = $quote->ingredientRequest;
        $karenderia = $ingredientRequest->karenderia;

        // Verify ownership
        if ($karenderia->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if request is still open
        if ($ingredientRequest->status !== 'open') {
            return response()->json(['message' => 'This request is no longer open for acceptance'], 400);
        }

        // Accept the quote
        $quote->accept();

        return response()->json([
            'message' => 'Quote accepted successfully',
            'data' => $quote->fresh(),
        ]);
    }

    /**
     * OWNER: Reject a supplier's quote
     */
    public function reject(Request $request, SupplierQuote $quote): JsonResponse
    {
        $user = Auth::user();

        $ingredientRequest = $quote->ingredientRequest;
        $karenderia = $ingredientRequest->karenderia;

        // Verify ownership
        if ($karenderia->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Reject the quote
        $quote->reject();

        return response()->json([
            'message' => 'Quote rejected',
            'data' => $quote,
        ]);
    }

    /**
     * SUPPLIER: Get their own submitted quotes
     */
    public function myQuotes(Request $request): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== 'supplier') {
            return response()->json(['message' => 'Only suppliers can view their quotes'], 403);
        }

        $status = $request->query('status');

        $query = SupplierQuote::where('supplier_id', $user->id)
            ->with(['ingredientRequest', 'ingredientRequest.karenderia:id,business_name,address'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $quotes = $query->paginate(15);

        return response()->json([
            'data' => $quotes,
        ]);
    }

    /**
     * Get all quotes for a specific ingredient request (only owner can view)
     */
    public function requestQuotes(IngredientRequest $ingredientRequest): JsonResponse
    {
        $user = Auth::user();
        $karenderia = $ingredientRequest->karenderia;

        if ($karenderia->owner_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $quotes = $ingredientRequest->quotes()
            ->with(['supplier:id,name,email,phone_number,rating'])
            ->orderByDesc('price_per_unit')
            ->get();

        return response()->json([
            'data' => $quotes,
        ]);
    }
}
