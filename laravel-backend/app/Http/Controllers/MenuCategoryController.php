<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Karenderia;
use App\Models\MenuCategory;

class MenuCategoryController extends Controller
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

            $categories = MenuCategory::where('karenderia_id', $karenderia->id)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return response()->json([
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch menu categories',
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
                    Rule::unique('menu_categories', 'name')->where(fn ($query) => $query->where('karenderia_id', $karenderia->id)),
                ],
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $validatedData['karenderia_id'] = $karenderia->id;

            $category = MenuCategory::create($validatedData);

            return response()->json([
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create category',
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

            $category = MenuCategory::where('karenderia_id', $karenderia->id)
                ->where('id', $id)
                ->first();

            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }

            return response()->json([
                'data' => $category
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch category',
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

            $category = MenuCategory::where('karenderia_id', $karenderia->id)
                ->where('id', $id)
                ->first();

            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }

            $validatedData = $request->validate([
                'name' => [
                    'sometimes',
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('menu_categories', 'name')
                        ->ignore($category->id)
                        ->where(fn ($query) => $query->where('karenderia_id', $karenderia->id)),
                ],
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer|min:0',
                'is_active' => 'nullable|boolean',
            ]);

            $category->update($validatedData);

            return response()->json([
                'message' => 'Category updated successfully',
                'data' => $category->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update category',
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

            $category = MenuCategory::where('karenderia_id', $karenderia->id)
                ->where('id', $id)
                ->first();

            if (!$category) {
                return response()->json(['error' => 'Category not found'], 404);
            }

            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete category',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
