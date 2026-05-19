<?php

namespace App\Http\Controllers;

use App\Models\IngredientRequest;
use App\Models\Karenderia;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class IngredientRequestController extends Controller
{
    /**
     * OWNER: Post a new ingredient request
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Verify user is a karenderia owner
        $karenderia = Karenderia::where('owner_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredient_type' => 'required|string', // Meat, Produce, Dairy, etc.
            'needed_quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string', // kg, lbs, pieces
            'needed_by_date' => 'required|date|after:today',
            'budget' => 'nullable|numeric|min:0',
            'delivery_address' => 'nullable|string',
            'expiry_hours' => 'nullable|integer|min:1|max:168',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        // Create location coordinates if provided
        $locationCoordinates = null;
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $locationCoordinates = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ];
        }

        $ingredientRequest = IngredientRequest::create([
            'karenderia_id' => $karenderia->id,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'ingredient_type' => $validated['ingredient_type'],
            'needed_quantity' => $validated['needed_quantity'],
            'unit' => $validated['unit'],
            'needed_by_date' => $validated['needed_by_date'],
            'budget' => $validated['budget'],
            'status' => 'open',
            'location_coordinates' => $locationCoordinates,
            'delivery_address' => $validated['delivery_address'],
            'expiry_hours' => $validated['expiry_hours'] ?? 48,
        ]);

        return response()->json([
            'message' => 'Ingredient request posted successfully',
            'data' => $ingredientRequest,
        ], 201);
    }

    /**
     * OWNER: View their own ingredient requests
     */
    public function ownerIndex(Request $request): JsonResponse
    {
        $user = Auth::user();
        $karenderia = Karenderia::where('owner_id', $user->id)->firstOrFail();

        $status = $request->query('status'); // 'open', 'accepted', 'completed'
        
        $query = IngredientRequest::where('karenderia_id', $karenderia->id)
            ->with(['acceptedSupplier:id,name,email,phone_number', 'quotes'])
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15);

        return response()->json([
            'data' => $requests,
        ]);
    }

    /**
     * OWNER: Get detail of a specific request with all supplier quotes
     */
    public function ownerShow(IngredientRequest $ingredientRequest): JsonResponse
    {
        $user = Auth::user();
        $karenderia = Karenderia::where('owner_id', $user->id)->firstOrFail();

        // Verify ownership
        if ($ingredientRequest->karenderia_id !== $karenderia->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ingredientRequest->load([
            'quotes.supplier:id,name,email,phone_number',
            'acceptedSupplier:id,name,email,phone_number',
        ]);

        return response()->json([
            'data' => $ingredientRequest,
        ]);
    }

    /**
     * OWNER: Update request status (mark as completed, cancel, etc.)
     */
    public function updateStatus(Request $request, IngredientRequest $ingredientRequest): JsonResponse
    {
        $user = Auth::user();
        $karenderia = Karenderia::where('owner_id', $user->id)->firstOrFail();

        if ($ingredientRequest->karenderia_id !== $karenderia->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:open,accepted,cancelled,completed',
        ]);

        $ingredientRequest->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Request status updated',
            'data' => $ingredientRequest,
        ]);
    }

    /**
     * SUPPLIER: Get all available ingredient requests (nearby suppliers)
     */
    public function supplierIndex(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Verify user is a supplier
        if ($user->role !== 'supplier') {
            return response()->json(['message' => 'Only suppliers can view requests'], 403);
        }

        // Get only OPEN requests that haven't expired
        $query = IngredientRequest::where('status', 'open')
            ->where('created_at', '>', now()->subHours(48)) // Default 48 hours
            ->with(['karenderia:id,business_name,address,city,province', 'quotes' => function ($q) use ($user) {
                $q->where('supplier_id', $user->id);
            }])
            ->orderByDesc('created_at');

        // TODO: Add geolocation filtering here
        // Filter by distance if coordinates provided
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $lat = $request->input('latitude');
            $lon = $request->input('longitude');
            $radius = $request->input('radius', 25); // km

            $query->whereRaw(
                "ST_Distance_Sphere(
                    POINT(JSON_EXTRACT(location_coordinates, '$.longitude'), JSON_EXTRACT(location_coordinates, '$.latitude')),
                    POINT(?, ?)
                ) / 1000 <= ?",
                [$lon, $lat, $radius]
            );
        }

        $requests = $query->paginate(15);

        return response()->json([
            'data' => $requests,
        ]);
    }

    /**
     * SUPPLIER: Get detail of a specific request with their quote status
     */
    public function supplierShow(IngredientRequest $ingredientRequest): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== 'supplier') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ingredientRequest->load([
            'karenderia:id,business_name,address,city,province,phone,business_email',
            'acceptedSupplier:id,name,email,phone_number',
        ]);

        // Get supplier's quote for this request (if exists)
        $myQuote = $ingredientRequest->quotes()
            ->where('supplier_id', $user->id)
            ->first();

        return response()->json([
            'data' => $ingredientRequest,
            'my_quote' => $myQuote,
        ]);
    }

    /**
     * SUPPLIER: Mark an accepted ingredient request as delivered
     */
    public function markDelivered(Request $request, IngredientRequest $ingredientRequest): JsonResponse
    {
        $user = Auth::user();

        // Verify user is the accepted supplier
        if ($ingredientRequest->accepted_supplier_id !== $user->id) {
            return response()->json(['message' => 'Only the accepted supplier can mark as delivered'], 403);
        }

        // Verify request is in accepted state
        if ($ingredientRequest->status !== 'accepted') {
            return response()->json(['message' => 'This request is not in accepted state'], 422);
        }

        // Verify both parties have accepted
        $acceptedQuote = $ingredientRequest->quotes()
            ->where('status', 'accepted')
            ->where('supplier_id', $user->id)
            ->first();

        if (!$acceptedQuote) {
            return response()->json(['message' => 'Quote not found or not accepted'], 404);
        }

        // Update request status to completed/delivered
        $ingredientRequest->update([
            'status' => 'completed',
        ]);

        return response()->json([
            'message' => 'Order marked as delivered successfully',
            'data' => $ingredientRequest,
        ]);
    }

}