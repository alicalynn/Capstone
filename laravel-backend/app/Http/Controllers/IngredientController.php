<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    /**
     * Get all ingredients
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Ingredients feature not yet implemented',
            'ingredients' => []
        ]);
    }

    /**
     * Add new ingredient
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Ingredients feature not yet implemented'
        ], 501); // Not Implemented
    }

    /**
     * Get specific ingredient
     */
    public function show($id): JsonResponse
    {
        return response()->json([
            'message' => 'Ingredients feature not yet implemented'
        ], 501); // Not Implemented
    }

    /**
     * Update ingredient
     */
    public function update(Request $request, $id): JsonResponse
    {
        return response()->json([
            'message' => 'Ingredients feature not yet implemented'
        ], 501); // Not Implemented
    }

    /**
     * Delete ingredient
     */
    public function destroy($id): JsonResponse
    {
        return response()->json([
            'message' => 'Ingredients feature not yet implemented'
        ], 501); // Not Implemented
    }
}
