<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class KarenderiaController extends Controller
{
    /**
     * Get all karenderias (only approved ones for customers)
     */
    public function index(): JsonResponse
    {
        try {
            // Only return approved karenderias for customers
            $karenderias = \App\Models\Karenderia::whereIn('status', ['approved', 'active'])
                ->with(['owner:id,name,email'])
                ->get()
                ->map(function ($karenderia) {
                    return [
                        'id' => $karenderia->id,
                        'name' => $karenderia->name,
                        'business_name' => $karenderia->business_name,
                        'description' => $karenderia->description,
                        'address' => $karenderia->address,
                        'latitude' => $karenderia->latitude,
                        'longitude' => $karenderia->longitude,
                        'rating' => $karenderia->average_rating,
                        'isOpen' => $this->isKarenderiaOpen($karenderia),
                        'cuisine' => 'Filipino', // Default for now
                        'priceRange' => '₱₱',
                        'imageUrl' => $karenderia->logo_url ?: '/assets/images/restaurant-placeholder.jpg',
                        'deliveryTime' => $karenderia->delivery_time_minutes . ' min',
                        'deliveryFee' => $karenderia->delivery_fee,
                        'minimumOrder' => 100, // Default
                        'isVerified' => $karenderia->status === 'approved',
                        'specialties' => ['Filipino Cuisine'], // Can be enhanced later
                        'phone' => $karenderia->phone,
                        'email' => $karenderia->email,
                        'operatingHours' => $this->formatOperatingHours($karenderia->operating_days),
                        'accepts_cash' => $karenderia->accepts_cash,
                        'accepts_online_payment' => $karenderia->accepts_online_payment,
                        'owner' => $karenderia->owner ? $karenderia->owner->name : 'Unknown',
                        'status' => $karenderia->status
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $karenderias,
                'message' => 'Approved karenderias retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve karenderias',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if karenderia is currently open
     */
    private function isKarenderiaOpen($karenderia)
    {
        // If no operating hours are set, consider it always open
        if (!$karenderia->opening_time || !$karenderia->closing_time) {
            return true; // Default to open if no hours specified
        }
        
        $now = now();
        $currentDay = strtolower($now->format('l')); // monday, tuesday, etc.
        $currentTime = $now->format('H:i');
        
        // Handle operating_days - ensure it's an array
        $operatingDays = $karenderia->operating_days;
        if (is_string($operatingDays)) {
            $operatingDays = json_decode($operatingDays, true) ?: [];
        }
        
        // Check if today is in operating days (if operating_days is empty array, consider always open)
        if (!empty($operatingDays) && !in_array($currentDay, $operatingDays)) {
            return false;
        }
        
        // Convert datetime objects to time string for comparison
        $openingTime = $karenderia->opening_time->format('H:i');
        $closingTime = $karenderia->closing_time->format('H:i');
        
        // Check if current time is within operating hours
        return $currentTime >= $openingTime && $currentTime <= $closingTime;
    }

    /**
     * Format operating hours for frontend
     */
    private function formatOperatingHours($operatingDays): array
    {
        $defaultHours = [
            'monday' => '8:00 AM - 9:00 PM',
            'tuesday' => '8:00 AM - 9:00 PM',
            'wednesday' => '8:00 AM - 9:00 PM',
            'thursday' => '8:00 AM - 9:00 PM',
            'friday' => '8:00 AM - 10:00 PM',
            'saturday' => '8:00 AM - 10:00 PM',
            'sunday' => '9:00 AM - 9:00 PM'
        ];
        
        // Handle string input - convert to array
        if (is_string($operatingDays)) {
            $operatingDays = json_decode($operatingDays, true) ?: [];
        }
        
        if (!$operatingDays) {
            return $defaultHours;
        }
        
        // If operating days is just an array of day names, use default hours
        if (is_array($operatingDays) && !isset($operatingDays['monday'])) {
            $hours = [];
            foreach ($defaultHours as $day => $time) {
                $hours[$day] = in_array($day, $operatingDays) ? $time : 'Closed';
            }
            return $hours;
        }
        
        return $operatingDays ?: $defaultHours;
    }

    /**
     * Get a specific karenderia
     */
    public function show($id): JsonResponse
    {
        try {
            // Fetch the actual karenderia from database
            $karenderia = \App\Models\Karenderia::with(['owner:id,name,email'])
                ->where('id', $id)
                ->whereIn('status', ['approved', 'active']) // Only show approved or active karenderias
                ->first();

            if (!$karenderia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karenderia not found or not approved',
                    'data' => null
                ], 404);
            }

            $karenderiaData = [
                'id' => $karenderia->id,
                'name' => $karenderia->name,
                'business_name' => $karenderia->business_name,
                'description' => $karenderia->description,
                'address' => $karenderia->address,
                'latitude' => $karenderia->latitude,
                'longitude' => $karenderia->longitude,
                'rating' => $karenderia->average_rating,
                'isOpen' => $this->isKarenderiaOpen($karenderia),
                'cuisine' => 'Filipino', // Default for now
                'priceRange' => '₱₱',
                'imageUrl' => $karenderia->logo_url ?: '/assets/images/restaurant-placeholder.jpg',
                'deliveryTime' => $karenderia->delivery_time_minutes . ' min',
                'deliveryFee' => $karenderia->delivery_fee,
                'minimumOrder' => 100, // Default
                'isVerified' => $karenderia->status === 'approved',
                'specialties' => ['Filipino Cuisine'], // Can be enhanced later
                'phone' => $karenderia->phone,
                'email' => $karenderia->email,
                'operatingHours' => $this->formatOperatingHours($karenderia->operating_days),
                'accepts_cash' => $karenderia->accepts_cash,
                'accepts_online_payment' => $karenderia->accepts_online_payment,
                'owner' => $karenderia->owner ? $karenderia->owner->name : 'Unknown',
                'status' => $karenderia->status
            ];

            return response()->json([
                'success' => true,
                'data' => $karenderiaData,
                'message' => 'Karenderia retrieved successfully'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve karenderia',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * Get current user's karenderia application/restaurant
     */
    public function myKarenderia(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $karenderia = \App\Models\Karenderia::where('owner_id', $user->id)->first();
            
            if (!$karenderia) {
                return response()->json([
                    'success' => false,
                    'message' => 'No karenderia application found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $karenderia->id,
                    'name' => $karenderia->name,
                    'business_name' => $karenderia->business_name,
                    'description' => $karenderia->description,
                    'address' => $karenderia->address,
                    'phone' => $karenderia->phone,
                    'email' => $karenderia->email,
                    'latitude' => $karenderia->latitude,
                    'longitude' => $karenderia->longitude,
                    'opening_time' => $karenderia->opening_time,
                    'closing_time' => $karenderia->closing_time,
                    'operating_days' => $karenderia->operating_days,
                    'delivery_fee' => $karenderia->delivery_fee,
                    'delivery_time_minutes' => $karenderia->delivery_time_minutes,
                    'accepts_cash' => $karenderia->accepts_cash,
                    'accepts_online_payment' => $karenderia->accepts_online_payment,
                    'status' => $karenderia->status,
                    'created_at' => $karenderia->created_at,
                    'updated_at' => $karenderia->updated_at,
                    'status_message' => $this->getStatusMessage($karenderia->status)
                ],
                'message' => 'Your karenderia information retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve karenderia information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the current owner's karenderia, including rejected applications.
     */
    public function updateMyKarenderiaData(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $karenderia = \App\Models\Karenderia::where('owner_id', $user->id)->first();

            if (!$karenderia) {
                return response()->json([
                    'success' => false,
                    'message' => 'No karenderia application found for this owner'
                ], 404);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'business_name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'address' => 'sometimes|string',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email',
                'business_email' => 'nullable|email',
                'city' => 'sometimes|string|max:100',
                'province' => 'sometimes|string|max:100',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'opening_time' => 'nullable|date_format:H:i',
                'closing_time' => 'nullable|date_format:H:i',
                'operating_days' => 'nullable|array',
                'delivery_fee' => 'nullable|numeric|min:0',
                'delivery_time_minutes' => 'nullable|integer|min:0',
                'accepts_cash' => 'nullable|boolean',
                'accepts_online_payment' => 'nullable|boolean'
            ]);

            if (isset($validatedData['business_name'])) {
                $validatedData['name'] = $validatedData['business_name'];
            }

            if (isset($validatedData['business_email'])) {
                $validatedData['email'] = $validatedData['business_email'];
            }

            $wasRejected = in_array($karenderia->status, ['rejected', 'inactive'], true);

            $updateData = array_merge($validatedData, [
                'status' => $wasRejected ? 'pending' : $karenderia->status,
            ]);

            if ($wasRejected) {
                $updateData['approved_at'] = null;
                $updateData['approved_by'] = null;
                $updateData['rejected_at'] = null;
                $updateData['rejection_reason'] = null;
            }

            $karenderia->update($updateData);
            $karenderia->refresh();

            return response()->json([
                'success' => true,
                'message' => $wasRejected
                    ? 'Karenderia application resubmitted successfully. It is now pending admin review.'
                    : 'Karenderia updated successfully',
                'data' => $karenderia
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update karenderia application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get status message for karenderia owner
     */
    private function getStatusMessage($status): string
    {
        switch ($status) {
            case 'pending':
                return 'Your karenderia application is under review. Please wait for admin approval.';
            case 'active':
                return 'Your karenderia is approved and active! Customers can now see your restaurant.';
            case 'inactive':
                return 'Your karenderia application was rejected or deactivated. Please contact admin for details.';
            default:
                return 'Status unknown. Please contact support.';
        }
    }

    /**
     * Store a new karenderia registration
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'address' => 'required|string',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'opening_time' => 'nullable|date_format:H:i',
                'closing_time' => 'nullable|date_format:H:i',
                'operating_days' => 'nullable|array',
                'delivery_fee' => 'nullable|numeric|min:0',
                'delivery_time_minutes' => 'nullable|integer|min:0',
                'accepts_cash' => 'boolean',
                'accepts_online_payment' => 'boolean'
            ]);

            $user = $request->user();
            
            // Check if user already has a karenderia
            $existingKarenderia = \App\Models\Karenderia::where('owner_id', $user->id)->first();
            if ($existingKarenderia) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a karenderia registered',
                    'data' => $existingKarenderia
                ], 409);
            }

            // Create karenderia with pending status
            $karenderia = \App\Models\Karenderia::create([
                'owner_id' => $user->id,
                'status' => 'pending', // Will need admin approval
                ...$validatedData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Karenderia application submitted successfully. Waiting for admin approval.',
                'data' => $karenderia
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to submit karenderia application',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search karenderias
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        $location = $request->get('location', '');
        $cuisine = $request->get('cuisine', '');

        // This would normally search in database
        // For now, return filtered mock data
        $karenderias = [
            [
                'id' => 1,
                'name' => 'Mama\'s Kitchen',
                'description' => 'Authentic Filipino home cooking',
                'address' => '123 Main St, Cebu City',
                'latitude' => 10.3157,
                'longitude' => 123.8854,
                'rating' => 4.5,
                'isOpen' => true,
                'cuisine' => 'Filipino',
                'priceRange' => '₱₱',
                'deliveryTime' => '25-35 min',
                'deliveryFee' => 25,
                'minimumOrder' => 150,
                'isVerified' => true
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $karenderias,
            'message' => 'Search results retrieved successfully',
            'query' => $query,
            'filters' => [
                'location' => $location,
                'cuisine' => $cuisine
            ]
        ]);
    }

    /**
     * Update karenderia information
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $karenderia = \App\Models\Karenderia::findOrFail($id);
            $user = $request->user();
            
            // Check if user owns this karenderia
            if ($karenderia->owner_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this karenderia'
                ], 403);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'address' => 'sometimes|string',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'opening_time' => 'nullable|date_format:H:i',
                'closing_time' => 'nullable|date_format:H:i',
                'operating_days' => 'nullable|array',
                'delivery_fee' => 'nullable|numeric|min:0',
                'delivery_time_minutes' => 'nullable|integer|min:0',
                'accepts_cash' => 'boolean',
                'accepts_online_payment' => 'boolean'
            ]);

            $karenderia->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Karenderia updated successfully',
                'data' => $karenderia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update karenderia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete karenderia
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $karenderia = \App\Models\Karenderia::findOrFail($id);
            $user = $request->user();
            
            // Check if user owns this karenderia or is admin
            if ($karenderia->owner_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to delete this karenderia'
                ], 403);
            }

            $karenderia->delete();

            return response()->json([
                'success' => true,
                'message' => 'Karenderia deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete karenderia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get nearby karenderias based on location
     */
    public function nearby(Request $request): JsonResponse
    {
        try {
            $latitude = $request->get('lat') ?: $request->get('latitude');
            $longitude = $request->get('lng') ?: $request->get('longitude');
            $radius = $request->get('radius', 5000); // Default 5km radius
            
            if (!$latitude || !$longitude) {
                return response()->json([
                    'success' => false,
                    'message' => 'Latitude and longitude are required'
                ], 400);
            }

            // Get all approved karenderias
            $karenderias = \App\Models\Karenderia::whereIn('status', ['approved', 'active'])
                ->with(['owner:id,name,email'])
                ->get()
                ->map(function ($karenderia) use ($latitude, $longitude) {
                    // Calculate distance
                    $distance = $this->calculateDistance(
                        $latitude, 
                        $longitude, 
                        $karenderia->latitude, 
                        $karenderia->longitude
                    );
                    
                    return [
                        'id' => $karenderia->id,
                        'name' => $karenderia->name,
                        'description' => $karenderia->description,
                        'address' => $karenderia->address,
                        'latitude' => $karenderia->latitude,
                        'longitude' => $karenderia->longitude,
                        'distance' => round($distance * 1000), // Convert to meters
                        'rating' => $karenderia->rating ?: 4.0,
                        'average_rating' => $karenderia->rating ?: 4.0,
                        'isOpen' => $this->isKarenderiaOpen($karenderia),
                        'cuisine' => 'Filipino',
                        'priceRange' => '₱₱',
                        'imageUrl' => $karenderia->logo_url ?: '/assets/images/restaurant-placeholder.jpg',
                        'deliveryTime' => ($karenderia->delivery_time_minutes ?: 30) . ' min',
                        'delivery_time_minutes' => $karenderia->delivery_time_minutes ?: 30,
                        'deliveryFee' => $karenderia->delivery_fee ?: 50,
                        'phone' => $karenderia->phone,
                        'email' => $karenderia->email,
                        'status' => $karenderia->status,
                        'owner' => $karenderia->owner ? $karenderia->owner->name : 'Unknown'
                    ];
                })
                ->filter(function ($karenderia) use ($radius) {
                    // Filter by radius (in meters)
                    return $karenderia['distance'] <= $radius;
                })
                ->sortBy('distance')
                ->values();

            return response()->json([
                'success' => true,
                'data' => $karenderias,
                'message' => 'Nearby karenderias retrieved successfully',
                'search_params' => [
                    'lat' => $latitude,
                    'lng' => $longitude,
                    'radius' => $radius . 'm',
                    'count' => $karenderias->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error finding nearby karenderias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2)
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $lat1Rad = deg2rad($lat1);
        $lng1Rad = deg2rad($lng1);
        $lat2Rad = deg2rad($lat2);
        $lng2Rad = deg2rad($lng2);

        $deltaLat = $lat2Rad - $lat1Rad;
        $deltaLng = $lng2Rad - $lng1Rad;

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1Rad) * cos($lat2Rad) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Update karenderia data (for settings page)
     */
    public function updateKarenderiaData(Request $request, $id): JsonResponse
    {
        try {
            $karenderia = \App\Models\Karenderia::findOrFail($id);
            $user = $request->user();
            
            // Check if user owns this karenderia
            if ($karenderia->owner_id !== $user->id && $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to update this karenderia'
                ], 403);
            }

            $validatedData = $request->validate([
                'business_name' => 'sometimes|string|max:255',
                'name' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'address' => 'sometimes|string',
                'phone' => 'nullable|string|max:20',
                'business_email' => 'nullable|email',
                'email' => 'nullable|email',
                'latitude' => 'nullable|numeric|between:-90,90',
                'longitude' => 'nullable|numeric|between:-180,180',
                'cuisine_type' => 'sometimes|string|max:50',
                'opening_time' => 'nullable|date_format:H:i',
                'closing_time' => 'nullable|date_format:H:i',
                'operating_days' => 'nullable|array',
                'delivery_fee' => 'nullable|numeric|min:0',
                'delivery_time_minutes' => 'nullable|integer|min:0',
                'accepts_cash' => 'boolean',
                'accepts_online_payment' => 'boolean'
            ]);

            // Map business_email to email if provided
            if (isset($validatedData['business_email'])) {
                $validatedData['email'] = $validatedData['business_email'];
                unset($validatedData['business_email']);
            }

            // Map business_name to name if provided
            if (isset($validatedData['business_name'])) {
                $validatedData['name'] = $validatedData['business_name'];
                $validatedData['business_name'] = $validatedData['business_name']; // Keep both for compatibility
            }

            $karenderia->update($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Karenderia updated successfully',
                'data' => $karenderia->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update karenderia',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the current authenticated user's karenderia
     */
    public function getMyKarenderia(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            if ($user->role !== 'karenderia_owner') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a karenderia owner'
                ], 403);
            }

            $karenderia = \App\Models\Karenderia::where('owner_id', $user->id)->first();
            
            if (!$karenderia) {
                return response()->json([
                    'success' => false,
                    'message' => 'No karenderia found for this owner'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $karenderia
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving karenderia: ' . $e->getMessage()
            ], 500);
        }
    }
}
