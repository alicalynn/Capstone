<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\RegistrationConfirmationNotification;
use App\Notifications\KarenderiaRegistrationConfirmation;
use App\Notifications\SupplierApprovedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
            'application_status' => 'approved',
            'email_notifications_enabled' => true
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
        if (is_string($request->operating_days)) {
            $decodedOperatingDays = json_decode($request->operating_days, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decodedOperatingDays)) {
                $request->merge(['operating_days' => $decodedOperatingDays]);
            }
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|same:password',
            'business_name' => 'required|string|max:255',
            'description' => 'required|string',
            'address' => 'required|string',
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
            'accepts_online_payment' => 'boolean',
                'business_permit' => 'required_without:business_permit_file|file|mimes:pdf,jpg,jpeg,png|max:5120',
                'business_permit_file' => 'required_without:business_permit|file|mimes:pdf,jpg,jpeg,png|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $businessPermitPath = null;

            if ($request->hasFile('business_permit_file')) {
                $file = $request->file('business_permit_file');
                $filename = time() . '_' . str_replace(' ', '_', $request->business_name) . '.' . $file->getClientOriginalExtension();
                $businessPermitPath = $file->storeAs('business-permits', $filename, 'public');
            } elseif ($request->hasFile('business_permit')) {
                $file = $request->file('business_permit');
                $filename = time() . '_' . str_replace(' ', '_', $request->business_name) . '.' . $file->getClientOriginalExtension();
                $businessPermitPath = $file->storeAs('business-permits', $filename, 'public');
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'karenderia_owner',
                'verified' => false,
                'email_notifications_enabled' => true
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
                'operating_days' => $request->operating_days ?? [],
                'delivery_fee' => $request->delivery_fee ?? 0,
                'delivery_time_minutes' => $request->delivery_time_minutes ?? 30,
                'accepts_cash' => $request->accepts_cash ?? true,
                'accepts_online_payment' => $request->accepts_online_payment ?? false,
                'business_permit' => $businessPermitPath,
                'status' => 'pending',
                'approved_at' => null,
                'approved_by' => null
            ]);

            // Send registration confirmation email, but do not fail the registration if mail is down
            try {
                $user->notify(new KarenderiaRegistrationConfirmation());
            } catch (\Throwable $notificationException) {
                Log::warning('Karenderia registration notification failed:', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'message' => $notificationException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Karenderia registration submitted successfully! Your business permit has been received. Your application is now pending admin approval. Please wait for approval before attempting to login.',
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
                    'address' => $karenderia->address,
                    'business_permit_url' => Storage::url($businessPermitPath)
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
     * Register a new supplier
     */
    public function registerSupplier(Request $request): JsonResponse
    {
        Log::info('Supplier registration request:', [
            'data' => $request->all(),
            'ip' => $request->ip()
        ]);

        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|min:3',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'confirmPassword' => 'required|string|min:6|same:password',
            'phoneNumber' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'business_permit_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'business_permit' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'
        ], [
            'username.required' => 'Username is required',
            'username.min' => 'Username must be at least 3 characters',
            'email.required' => 'Email is required',
            'email.email' => 'Email must be valid',
            'email.unique' => 'This email is already registered',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 6 characters',
            'confirmPassword.required' => 'Confirm password is required',
            'confirmPassword.min' => 'Confirm password must be at least 6 characters',
            'confirmPassword.same' => 'Passwords do not match'
        ]);

        if ($validator->fails()) {
            Log::warning('Supplier registration validation failed:', $validator->errors()->toArray());
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $businessPermitPath = null;

            if ($request->hasFile('business_permit_file')) {
                $permitFile = $request->file('business_permit_file');
                $permitBaseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->username ?: $request->email);
                $permitFileName = time() . '_' . $permitBaseName . '.' . $permitFile->getClientOriginalExtension();
                $businessPermitPath = $permitFile->storeAs('business-permits', $permitFileName, 'public');
            } elseif ($request->hasFile('business_permit')) {
                $permitFile = $request->file('business_permit');
                $permitBaseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $request->username ?: $request->email);
                $permitFileName = time() . '_' . $permitBaseName . '.' . $permitFile->getClientOriginalExtension();
                $businessPermitPath = $permitFile->storeAs('business-permits', $permitFileName, 'public');
            }

            $user = User::create([
                'name' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phoneNumber,
                'address' => $request->address,
                'role' => 'supplier',
                'verified' => false,
                'application_status' => 'pending',
                'business_permit' => $businessPermitPath,
                'email_notifications_enabled' => true
            ]);

            // Send registration confirmation email, but do not fail the registration if mail is down
            try {
                $user->notify(new RegistrationConfirmationNotification('supplier'));
            } catch (\Throwable $notificationException) {
                Log::warning('Supplier registration notification failed:', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'message' => $notificationException->getMessage(),
                ]);
            }

            Log::info('Supplier registered successfully:', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'success' => true,
                'message' => 'Supplier registration submitted successfully! Your application is pending admin approval.',
                'status' => 'pending_approval',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'business_permit_url' => $businessPermitPath ? url('/business-permits/' . basename($businessPermitPath)) : null
                ],
                'next_step' => 'Please wait for admin approval. You will receive an email once your application is reviewed.'
            ], 201);

        } catch (\Exception $e) {
            Log::error('Supplier registration error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reapply as karenderia owner with updated permit
     */
    public function reapplyOwner(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'business_permit_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)
                ->where('role', 'karenderia_owner')
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'No karenderia owner account found with this email',
                    'errors' => ['email' => ['Owner account not found']]
                ], 404);
            }

            $karenderia = $user->karenderia;

            if (!$karenderia) {
                return response()->json([
                    'message' => 'Karenderia application not found for this owner',
                ], 404);
            }

            if ($karenderia->status !== 'rejected') {
                return response()->json([
                    'message' => 'You can only reapply if your application was previously rejected',
                    'current_status' => $karenderia->status
                ], 422);
            }

            $businessPermitPath = $request->file('business_permit_file')->store('business-permits', 'public');

            $karenderia->update([
                'business_permit' => $businessPermitPath,
                'status' => 'pending',
                'approved_at' => null,
                'approved_by' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
                'reapplication_count' => $karenderia->reapplication_count + 1
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your reapplication has been submitted successfully! Your updated permit is now pending admin review. Please check your email for updates.',
                'status' => 'pending_approval',
                'karenderia' => [
                    'id' => $karenderia->id,
                    'business_name' => $karenderia->business_name,
                    'status' => $karenderia->status,
                    'reapplication_count' => $karenderia->reapplication_count
                ],
                'next_step' => 'Wait for admin approval, then login with your credentials'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Reapplication failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reapply as supplier with an updated business permit.
     */
    public function reapplySupplier(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'business_permit_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('email', $request->email)
                ->where('role', 'supplier')
                ->first();

            if (!$user) {
                return response()->json([
                    'message' => 'No supplier account found with this email',
                    'errors' => ['email' => ['Supplier account not found']]
                ], 404);
            }

            if ($user->application_status !== 'rejected') {
                return response()->json([
                    'message' => 'You can only reapply if your application was previously rejected',
                    'current_status' => $user->application_status
                ], 422);
            }

            $businessPermitPath = $request->file('business_permit_file')->store('business-permits', 'public');

            $user->update([
                'business_permit' => $businessPermitPath,
                'application_status' => 'pending',
                'verified' => false
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Your supplier reapplication has been submitted successfully! Your updated permit is now pending admin review.',
                'status' => 'pending_approval',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                    'role' => $user->role,
                    'business_permit_url' => url('/business-permits/' . basename($businessPermitPath))
                ],
                'next_step' => 'Wait for admin approval, then login with your credentials'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Reapplication failed',
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

        if ($user->disabled_at) {
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Your account has been disabled. Please contact admin support.'
            ], 403);
        }

        // Only check karenderia business approval for karenderia owners
        if ($user->role === 'karenderia_owner') {
            $karenderia = $user->karenderia;
            
            if (!$karenderia) {
                return response()->json([
                    'message' => 'Karenderia application not found'
                ], 403);
            }
            
            // Check for rejected status FIRST so reapply button shows
            if ($karenderia->status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot login because your owner application was rejected by admin. Reason: ' . ($karenderia->rejection_reason ?? 'Not specified'),
                    'status' => 'rejected',
                    'application_details' => [
                        'business_name' => $karenderia->business_name,
                        'rejected_at' => $karenderia->rejected_at ? $karenderia->rejected_at->format('M d, Y') : null,
                        'rejection_reason' => $karenderia->rejection_reason,
                        'status' => 'rejected'
                    ]
                ], 403);
            }
            
            if (!$user->verified || $karenderia->status === 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot login yet because your owner account is still pending admin verification.',
                    'status' => 'pending_approval',
                    'application_details' => [
                        'business_name' => $karenderia->business_name,
                        'submitted_at' => $karenderia->created_at->format('M d, Y'),
                        'status' => 'pending'
                    ]
                ], 403);
            }

            if (!in_array($karenderia->status, ['approved', 'active'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot login yet because your owner account is still pending admin verification.',
                    'status' => 'pending_approval'
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
                'verified' => $user->verified,
                'disabled_at' => $user->disabled_at
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
                'verified' => $user->verified,
                'disabled_at' => $user->disabled_at
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
