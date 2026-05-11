<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\IngredientRequest;
use App\Models\Karenderia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OwnerIngredientRequestController extends Controller
{
    /**
     * Display owner's ingredient requests
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $karenderia = Karenderia::where('owner_id', $user->id)->firstOrFail();

        $status = $request->query('status');

        $query = IngredientRequest::where('karenderia_id', $karenderia->id)
            ->with(['acceptedSupplier', 'quotes'])
            ->withCount('quotes')
            ->orderByDesc('created_at');

        if ($status) {
            $query->where('status', $status);
        }

        $requests = $query->paginate(15);

        return view('owner.ingredient-requests.index', compact('requests'));
    }

    /**
     * Show create request form
     */
    public function create()
    {
        return view('owner.ingredient-requests.create');
    }

    /**
     * Store new ingredient request
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $karenderia = Karenderia::where('owner_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredient_type' => 'required|string',
            'needed_quantity' => 'required|numeric|min:0.01',
            'unit' => 'required|string',
            'needed_by_date' => 'required|date|after:today',
            'budget' => 'nullable|numeric|min:0',
            'delivery_address' => 'nullable|string',
            'expiry_hours' => 'nullable|integer|min:1|max:168',
        ]);

        $locationCoordinates = null;
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $locationCoordinates = [
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
            ];
        }

        IngredientRequest::create([
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

        return redirect()
            ->route('owner.ingredient-requests')
            ->with('success', 'Ingredient request posted successfully! Suppliers will see it shortly.');
    }

    /**
     * Show ingredient request detail with quotes
     */
    public function show(IngredientRequest $ingredientRequest)
    {
        $user = Auth::user();
        $karenderia = Karenderia::where('owner_id', $user->id)->firstOrFail();

        if ($ingredientRequest->karenderia_id !== $karenderia->id) {
            return redirect()->route('owner.ingredient-requests')
                ->with('error', 'Unauthorized');
        }

        $ingredientRequest->load(['quotes.supplier', 'acceptedSupplier']);

        return view('owner.ingredient-requests.show', compact('ingredientRequest'));
    }

    /**
     * Update request status
     */
    public function updateStatus(Request $request, IngredientRequest $ingredientRequest)
    {
        $user = Auth::user();
        $karenderia = Karenderia::where('owner_id', $user->id)->firstOrFail();

        if ($ingredientRequest->karenderia_id !== $karenderia->id) {
            return redirect()->route('owner.ingredient-requests')
                ->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'status' => 'required|in:open,accepted,cancelled,completed',
        ]);

        $ingredientRequest->update(['status' => $validated['status']]);

        return redirect()->back()
            ->with('success', 'Request status updated successfully');
    }
}
