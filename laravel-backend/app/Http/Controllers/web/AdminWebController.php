<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Karenderia;

class AdminWebController extends Controller
{
    public function loginForm()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $credentials = $request->only('email', 'password');
            
            Log::info('Admin login attempt', ['email' => $credentials['email']]);
            
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                Log::info('Authentication successful', ['user_id' => $user->id, 'role' => $user->role]);
                
                if ($user->role === 'admin') {
                    $request->session()->regenerate();
                    Log::info('Admin login successful', ['user_id' => $user->id]);
                    return redirect()->route('admin.dashboard')->with('success', 'Welcome to Admin Dashboard!');
                } else {
                    Auth::logout();
                    Log::warning('Non-admin user tried to access admin area', ['user_id' => $user->id, 'role' => $user->role]);
                    return back()->withErrors(['email' => 'Access denied. Admin privileges required.'])->withInput();
                }
            }

            Log::warning('Invalid login credentials', ['email' => $credentials['email']]);
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in admin login', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Session\TokenMismatchException $e) {
            Log::error('CSRF token mismatch in admin login');
            return redirect()->route('admin.login')->withErrors(['email' => 'Session expired. Please try again.']);
        } catch (\Exception $e) {
            Log::error('Admin login error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['email' => 'Login failed. Please try again.'])->withInput();
        }
    }

    public function dashboard()
    {
        $pendingKarenderias = Karenderia::where('status', 'pending')->count();
        $pendingSuppliers = User::where('role', 'supplier')
            ->where(function ($query) {
                $query->where('application_status', 'pending')
                      ->orWhereNull('application_status');
            })
            ->count();

        $stats = [
            'total_users' => User::count(),
            'total_customers' => User::where('role', 'customer')->count(),
            'total_karenderia_owners' => User::where('role', 'karenderia_owner')->count(),
            'total_suppliers' => User::where('role', 'supplier')->count(),
            'pending_karenderias' => $pendingKarenderias,
            'pending_suppliers' => $pendingSuppliers,
            'pending_approvals' => $pendingKarenderias + $pendingSuppliers,
            'approved_karenderias' => Karenderia::where('status', 'approved')->count(),
            'rejected_karenderias' => Karenderia::where('status', 'rejected')->count(),
        ];

        $recent_registrations = Karenderia::with('owner')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recent_registrations'))
            ->with('pendingCount', $stats['pending_approvals']);
    }

    public function users(Request $request)
    {
        $role = strtolower(trim((string) $request->get('role', '')));
        $verifiedFilter = strtolower(trim((string) $request->get('verified', '')));
        $appStatus = strtolower(trim((string) $request->get('app_status', '')));

        // Normalize non-filter sentinel values from UI selects.
        if (in_array($role, ['all', 'all_roles'], true)) {
            $role = '';
        }

        if (in_array($verifiedFilter, ['all', 'all_users', 'any'], true)) {
            $verifiedFilter = '';
        }

        if (in_array($appStatus, ['all', 'all_status', 'any'], true)) {
            $appStatus = '';
        }

        $query = User::with('karenderia');

        // Filter by role
        if ($role !== '') {
            $query->where('role', $role);
        }

        // Filter by verified status
        if ($verifiedFilter !== '') {
            $verified = in_array($verifiedFilter, ['yes', 'true', '1', 'verified'], true);
            
            if ($role === 'supplier' || $role === 'karenderia_owner') {
                // For specific suppliers and owners, check application_status or karenderia status
                if ($verified) {
                    $query->where(function ($q) {
                        $q->where('verified', true)
                          ->orWhere('application_status', 'approved')
                          ->orWhereHas('karenderia', function ($subQ) {
                              $subQ->whereIn('status', ['approved', 'active']);
                          });
                    });
                } else {
                    $query->where(function ($q) {
                        $q->where('application_status', '!=', 'approved')
                          ->orWhereDoesntHave('karenderia');
                    });
                }
            } else {
                // For customers and others, or when no role is specified:
                // Check both verified flag AND application_status
                $query->where(function ($q) use ($verified) {
                    if ($verified) {
                        $q->where('verified', true)
                          ->orWhere('application_status', 'approved')
                          ->orWhereHas('karenderia', function ($subQ) {
                              $subQ->whereIn('status', ['approved', 'active']);
                          });
                    } else {
                        $q->where('verified', false)
                          ->orWhere('application_status', '!=', 'approved')
                          ->orWhereDoesntHave('karenderia');
                    }
                });
            }
        }

        // Filter by application status (for owners and suppliers)
        if ($appStatus !== '') {
            $status = $appStatus;
            if ($status === 'active') {
                $query->where(function ($q) {
                    $q->where('application_status', 'approved')
                      ->orWhereHas('karenderia', function ($subQ) {
                          $subQ->whereIn('status', ['approved', 'active']);
                      });
                });
            } else {
                $query->where(function ($q) use ($status) {
                    $q->where('application_status', $status)
                      ->orWhereHas('karenderia', function ($subQ) use ($status) {
                          $subQ->where('status', $status);
                      });
                });
            }
        }

        // Filter by registration date range (check for null, not just empty string)
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        // Search by name or email
        if ($request->has('search') && $request->get('search') !== '') {
            $search = strtolower($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);
        
        $pendingCount = Karenderia::where('status', 'pending')->count()
            + User::where('role', 'supplier')
                ->where(function ($query) {
                    $query->where('application_status', 'pending')
                          ->orWhereNull('application_status');
                })
                ->count();
        
        // Pass filter parameters back to view for form persistence
        $filters = [
            'role' => $role,
            'verified' => $verifiedFilter,
            'app_status' => $appStatus,
            'date_from' => $request->get('date_from', ''),
            'date_to' => $request->get('date_to', ''),
            'search' => $request->get('search', ''),
        ];
        
        return view('admin.users', compact('users', 'filters'))->with('pendingCount', $pendingCount);
    }

    public function karenderias()
    {
        $karenderias = Karenderia::with('owner')->orderBy('created_at', 'desc')->paginate(20);
        $pendingCount = Karenderia::where('status', 'pending')->count()
            + User::where('role', 'supplier')
                ->where(function ($query) {
                    $query->where('application_status', 'pending')
                          ->orWhereNull('application_status');
                })
                ->count();
        return view('admin.karenderias', compact('karenderias'))->with('pendingCount', $pendingCount);
    }

    /**
     * Show edit form for a user
     */
    public function editUser($id)
    {
        $user = User::with('karenderia')->findOrFail($id);
        return view('admin.edit-user', compact('user'));
    }

    /**
     * Update user details
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            // Store old values for audit log
            $oldValues = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];

            $user->update($validated);

            // Log the changes
            $changes = [];
            foreach ($validated as $key => $newValue) {
                if ($oldValues[$key] !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValues[$key],
                        'new' => $newValue
                    ];
                }
            }

            if (!empty($changes)) {
                Log::info("User updated by admin", [
                    'user_id' => $user->id,
                    'admin_id' => Auth::id(),
                    'changes' => $changes,
                    'timestamp' => now()
                ]);
            }

            return redirect()->route('admin.users')
                ->with('success', "User '{$user->name}' has been updated successfully!");
        } catch (\Exception $e) {
            Log::error('Error updating user', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);
            return redirect()->route('admin.edit-user', $id)
                ->with('error', 'Failed to update user. Please try again.');
        }
    }

    /**
     * Show edit form for a karenderia application
     */
    public function editKarenderia($id)
    {
        $karenderia = Karenderia::with('owner')->findOrFail($id);
        return view('admin.edit-karenderia', compact('karenderia'));
    }

    /**
     * Update karenderia details
     */
    public function updateKarenderia(Request $request, $id)
    {
        $karenderia = Karenderia::findOrFail($id);
        $owner = $karenderia->owner;

        $validated = $request->validate([
            // Owner info
            'owner_name' => 'nullable|string|max:255',
            'owner_email' => 'nullable|email',
            'owner_phone' => 'nullable|string|max:20',
            // Karenderia info
            'name' => 'required|string|max:255',
            'business_name' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'business_email' => 'nullable|email',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'opening_time' => 'nullable|date_format:H:i',
            'closing_time' => 'nullable|date_format:H:i',
        ]);

        try {
            // Store old values for audit log
            $oldKarenderiaValues = [
                'name' => $karenderia->name,
                'business_name' => $karenderia->business_name,
                'description' => $karenderia->description,
                'phone' => $karenderia->phone,
                'business_email' => $karenderia->business_email,
                'address' => $karenderia->address,
                'city' => $karenderia->city,
                'province' => $karenderia->province,
                'opening_time' => $karenderia->opening_time,
                'closing_time' => $karenderia->closing_time,
            ];

            $oldOwnerValues = [
                'name' => $owner?->name,
                'email' => $owner?->email,
                'phone' => $owner?->phone,
            ];

            // Update karenderia
            $karenderiaData = [
                'name' => $validated['name'],
                'business_name' => $validated['business_name'] ?? $validated['name'],
                'description' => $validated['description'],
                'phone' => $validated['phone'],
                'business_email' => $validated['business_email'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'province' => $validated['province'],
                'opening_time' => $validated['opening_time'],
                'closing_time' => $validated['closing_time'],
            ];

            $karenderia->update($karenderiaData);

            // Update owner if provided
            if ($owner && ($validated['owner_name'] || $validated['owner_email'] || $validated['owner_phone'])) {
                $ownerData = [];
                if ($validated['owner_name']) $ownerData['name'] = $validated['owner_name'];
                if ($validated['owner_email']) $ownerData['email'] = $validated['owner_email'];
                if ($validated['owner_phone']) $ownerData['phone'] = $validated['owner_phone'];
                
                $owner->update($ownerData);
            }

            // Log the changes
            $changes = [];
            foreach ($oldKarenderiaValues as $key => $oldValue) {
                $newValue = $karenderiaData[$key] ?? null;
                if ($oldValue !== $newValue && $newValue !== null) {
                    $changes['karenderia'][$key] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }

            if ($owner) {
                foreach ($oldOwnerValues as $key => $oldValue) {
                    $newValue = $owner->{$key};
                    if ($oldValue !== $newValue && $newValue !== null) {
                        $changes['owner'][$key] = [
                            'old' => $oldValue,
                            'new' => $newValue
                        ];
                    }
                }
            }

            if (!empty($changes)) {
                Log::info("Karenderia updated by admin", [
                    'karenderia_id' => $karenderia->id,
                    'admin_id' => Auth::id(),
                    'changes' => $changes,
                    'timestamp' => now()
                ]);
            }

            return redirect()->route('admin.review-application', $karenderia->id)
                ->with('success', "Karenderia '{$karenderia->name}' and owner details have been updated successfully!");
        } catch (\Exception $e) {
            Log::error('Error updating karenderia', [
                'error' => $e->getMessage(),
                'karenderia_id' => $id
            ]);
            return redirect()->route('admin.edit-karenderia', $id)
                ->with('error', 'Failed to update karenderia. Please try again.');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', 'Logged out successfully!');
    }
}