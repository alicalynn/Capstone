<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuCategoryController extends Controller
{
    /**
     * Get all menu categories for owner's karenderia
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Menu categories feature not yet implemented',
            'categories' => []
        ]);
    }

    /**
     * Create a new category
     */
    public function store(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Menu categories feature not yet implemented'
        ], 501); // Not Implemented
    }

    /**
     * Get specific category
     */
    public function show($id): JsonResponse
    {
        return response()->json([
            'message' => 'Menu categories feature not yet implemented'
        ], 501); // Not Implemented
    }

    /**
     * Update category
     */
    public function update(Request $request, $id): JsonResponse
    {
        return response()->json([
            'message' => 'Menu categories feature not yet implemented'
        ], 501); // Not Implemented
    }

    /**
     * Delete category
     */
    public function destroy($id): JsonResponse
    {
        return response()->json([
            'message' => 'Menu categories feature not yet implemented'
        ], 501); // Not Implemented
    }
}
