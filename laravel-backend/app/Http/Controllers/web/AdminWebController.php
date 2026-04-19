<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login')->with('success', 'Logged out successfully!');
    }
}