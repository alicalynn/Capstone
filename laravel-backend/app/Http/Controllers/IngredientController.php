<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Karenderia;
use App\Models\Ingredient;

class IngredientController extends Controller
{
    private function getOwnerKarenderia(Request $request): ?Karenderia
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return Karenderia::where('owner_id', $user->id)->first();
    }

    public function index()
    {
        try {
            $karenderia = $this->getOwnerKarenderia(request());

            if (!$karenderia) {
                return response()->json(['error' => 'No karenderia found for this user'], 403);
            }

            $ingredients = Ingredient::where('karenderia_id', $karenderia->id)
                ->orderBy('name')
                ->get();

            return response()->json([
                'data' => $ingredients
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch ingredients',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $karenderia = $this->getOwnerKarenderia($request);

            if (!$karenderia) {
                return response()->json(['error' => 'No karenderia found for this user'], 403);
            }

            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('ingredients', 'name')->where(fn ($query) => $query->where('karenderia_id', $karenderia->id)),
                ],
                'menu_category_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('menu_categories', 'id')->where(fn ($query) => $query->where('karenderia_id', $karenderia->id)),
                ],
                'description' => 'nullable|string',
                'unit' => 'nullable|string|max:100',
                'is_active' => 'nullable|boolean',
            ]);

            $validatedData['karenderia_id'] = $karenderia->id;

            $ingredient = Ingredient::create($validatedData);

            return response()->json([
                'message' => 'Ingredient created successfully',
                'data' => $ingredient
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create ingredient',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $karenderia = $this->getOwnerKarenderia($request);

            if (!$karenderia) {
                return response()->json(['error' => 'No karenderia found for this user'], 403);
            }

            $ingredient = Ingredient::where('karenderia_id', $karenderia->id)
                ->where('id', $id)
                ->first();

            if (!$ingredient) {
                return response()->json(['error' => 'Ingredient not found'], 404);
            }

            return response()->json([
                'data' => $ingredient
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch ingredient',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $karenderia = $this->getOwnerKarenderia($request);

            if (!$karenderia) {
                return response()->json(['error' => 'No karenderia found for this user'], 403);
            }

            $ingredient = Ingredient::where('karenderia_id', $karenderia->id)
                ->where('id', $id)
                ->first();

            if (!$ingredient) {
                return response()->json(['error' => 'Ingredient not found'], 404);
            }

            $validatedData = $request->validate([
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('ingredients', 'name')
                        ->ignore($ingredient->id)
                        ->where(fn ($query) => $query->where('karenderia_id', $karenderia->id)),
                ],
                'menu_category_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('menu_categories', 'id')->where(fn ($query) => $query->where('karenderia_id', $karenderia->id)),
                ],
                'description' => 'nullable|string',
                'unit' => 'nullable|string|max:100',
                'is_active' => 'nullable|boolean',
            ]);

            $ingredient->update($validatedData);

            return response()->json([
                'message' => 'Ingredient updated successfully',
                'data' => $ingredient->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update ingredient',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $karenderia = $this->getOwnerKarenderia($request);

            if (!$karenderia) {
                return response()->json(['error' => 'No karenderia found for this user'], 403);
            }

            $ingredient = Ingredient::where('karenderia_id', $karenderia->id)
                ->where('id', $id)
                ->first();

            if (!$ingredient) {
                return response()->json(['error' => 'Ingredient not found'], 404);
            }

            $ingredient->delete();

            return response()->json([
                'message' => 'Ingredient deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete ingredient',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
