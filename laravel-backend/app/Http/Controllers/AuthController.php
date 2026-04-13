<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password',
            'role' => 'in:customer,karenderia_owner,supplier'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'verified' => true,
            'application_status' => 'approved'
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'displayName' => $user->name,
                'role' => $user->role,
                'verified' => $user->verified
            ],
            'access_token' => $token,
            'token_type' => 'Bearer'
        ], 201);
    }

    /**
     * Register a new karenderia owner with business details
     */
    public function registerKarenderiaOwner(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password',
            'business_name' => 'required|string|max:255',
            'description' => 'required|string|min:10',
            'address' => 'required|string|min:10',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email|max:255',
            'opening_time' => 'nullable|string',
            'closing_time' => 'nullable|string',
            'operating_days' => 'nullable|array',
            'delivery_fee' => 'nullable|numeric|min:0',
            'delivery_time_minutes' => 'nullable|integer|min:0',
            'accepts_cash' => 'boolean',
            'accepts_online_payment' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'karenderia_owner',
                'verified' => false
            ]);

            $karenderia = $user->karenderia()->create([
                'name' => $request->business_name,
                'business_name' => $request->business_name,
                'description' => $request->description,
                'address' => $request->address,
                'city' => $request->city,
                'province' => $request->province,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'phone' => $request->phone,
                'business_email' => $request->business_email,
                'opening_time' => $request->opening_time ?? '09:00',
                'closing_time' => $request->closing_time ?? '21:00',
                'operating_days' => json_encode($request->operating_days ?? []),
                'delivery_fee' => $request->delivery_fee ?? 0,
                'delivery_time_minutes' => $request->delivery_time_minutes ?? 30,
                'accepts_cash' => $request->accepts_cash ?? true,
                'accepts_online_payment' => $request->accepts_online_payment ?? false,
                'status' => 'pending',
                'approved_at' => null,
                'approved_by' => null
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Karenderia registration submitted successfully! Your application is now pending admin approval. Please wait for approval before attempting to login.',
                'status' => 'pending_approval',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role
                ],
                'karenderia' => [
                    'id' => $karenderia->id,
                    'business_name' => $karenderia->business_name,
                    'status' => $karenderia->status,
                    'address' => $karenderia->address
                ],
                'next_step' => 'Wait for admin approval, then login with your credentials'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        // Log the incoming request for debugging
        Log::info('Login attempt:', [
            'request_data' => $request->all(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            Log::warning('Login validation failed:', $validator->errors()->toArray());
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            Log::warning('Login auth failed for email: ' . $request->email);
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401)->header('Access-Control-Allow-Origin', '*')
                     ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                     ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        }

        /** @var User $user */
        $user = Auth::user();
        Log::info('User authenticated successfully:', ['user_id' => $user->id, 'email' => $user->email, 'role' => $user->role]);

        // Only check karenderia business approval for karenderia owners
        if ($user->role === 'karenderia_owner') {
            $karenderia = $user->karenderia;
            
            if (!$karenderia) {
                return response()->json([
                    'message' => 'Karenderia application not found'
                ], 403);
            }
            
            if (false && $karenderia->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your karenderia application is still pending admin approval. Please wait for approval before logging in.',
                    'status' => 'pending_approval',
                    'application_details' => [
                        'business_name' => $karenderia->business_name,
                        'submitted_at' => $karenderia->created_at->format('M d, Y'),
                        'status' => 'pending'
                    ]
                ], 403);
            }
            
            if (false && $karenderia->status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'Your karenderia application was rejected. Reason: ' . ($karenderia->rejection_reason ?? 'Not specified'),
                    'status' => 'rejected',
                    'application_details' => [
                        'business_name' => $karenderia->business_name,
                        'rejected_at' => $karenderia->rejected_at ? $karenderia->rejected_at->format('M d, Y') : null,
                        'rejection_reason' => $karenderia->rejection_reason,
                        'status' => 'rejected'
                    ]
                ], 403);
            }
        }

        // Supplier login approval check
        if ($user->role === 'supplier') {
            if ($user->application_status !== 'approved') {
                $status = $user->application_status ?? 'pending';

                if ($status === 'rejected') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your supplier application was rejected. Please contact admin support.',
                        'status' => 'rejected',
                    ], 403);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Your supplier application is still pending admin approval.',
                    'status' => 'pending_approval',
                ], 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Include karenderia status for approved owners
        $response = [
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'displayName' => $user->name,
                'role' => $user->role,
                'verified' => $user->verified
            ],
            'access_token' => $token,
            'token_type' => 'Bearer'
        ];

        // Add karenderia info for all owners so the frontend can route them correctly
        if ($user->role === 'karenderia_owner' && $user->karenderia) {
            $response['karenderia'] = [
                'id' => $user->karenderia->id,
                'business_name' => $user->karenderia->business_name,
                'status' => $user->karenderia->status,
                'approved_at' => $user->karenderia->approved_at ? $user->karenderia->approved_at->format('M d, Y') : null,
                'rejected_at' => $user->karenderia->rejected_at ? $user->karenderia->rejected_at->format('M d, Y') : null,
                'rejection_reason' => $user->karenderia->rejection_reason
            ];
        }

        return response()->json($response)->header('Access-Control-Allow-Origin', '*')
                                        ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
                                        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'displayName' => $user->name,
                'role' => $user->role,
                'verified' => $user->verified
            ]
        ]);
    }

    /**
     * Reset password (placeholder)
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // TODO: Implement actual password reset logic
        return response()->json([
            'message' => 'Password reset email sent'
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|same:new_password'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Verify email (placeholder)
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        // TODO: Implement email verification logic
        return response()->json([
            'message' => 'Email verified successfully'
        ]);
    }

    /**
     * Resend verification email (placeholder)
     */
    public function resendVerification(Request $request): JsonResponse
    {
        // TODO: Implement resend verification logic
        return response()->json([
            'message' => 'Verification email sent'
        ]);
    }
}
