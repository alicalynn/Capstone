<?php

namespace App\Http\Controllers;

use App\Models\Karenderia;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware(function ($request, $next) {
            if (auth()->user()->role !== 'admin') {
                return response()->json(['message' => 'Access denied. Admin privileges required.'], 403);
            }
            return $next($request);
        });
    }

    /**
     * Get dashboard overview
     */
    public function dashboard()
    {
        $totalKarenderias = Karenderia::count();
        $activeKarenderias = Karenderia::where('status', 'active')->count();
        $pendingKarenderias = Karenderia::where('status', 'pending')->count();
        $pendingSuppliers = User::where('role', 'supplier')
            ->where('application_status', 'pending')
            ->count();
        
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total_amount');
        $totalProfit = Order::where('payment_status', 'paid')
            ->whereNotNull('total_cost')
            ->selectRaw('SUM(total_amount - total_cost) as profit')
            ->value('profit') ?? 0;
        
        $totalOrders = Order::count();
        $todaysOrders = Order::whereDate('created_at', today())->count();
        
        $totalUsers = User::where('role', 'customer')->count();
        $totalOwners = User::where('role', 'karenderia_owner')->count();
        $totalSuppliers = User::where('role', 'supplier')->count();

        return response()->json([
            'overview' => [
                'total_karenderias' => $totalKarenderias,
                'active_karenderias' => $activeKarenderias,
                'pending_karenderias' => $pendingKarenderias,
                'pending_suppliers' => $pendingSuppliers,
                'pending_approvals' => $pendingKarenderias + $pendingSuppliers,
                'total_revenue' => $totalRevenue,
                'total_profit' => $totalProfit,
                'total_orders' => $totalOrders,
                'todays_orders' => $todaysOrders,
                'total_customers' => $totalUsers,
                'total_owners' => $totalOwners,
                'total_suppliers' => $totalSuppliers,
            ]
        ]);
    }

    /**
     * Get all karenderias with their stats
     */
    public function karenderias(Request $request)
    {
        $query = Karenderia::with(['owner:id,name,email'])
            ->withCount(['orders', 'menuItems']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by name or owner
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhereHas('owner', function($subQ) use ($search) {
                      $subQ->where('name', 'LIKE', "%{$search}%")
                           ->orWhere('email', 'LIKE', "%{$search}%");
                  });
            });
        }

        $karenderias = $query->paginate($request->get('per_page', 15));

        // Add revenue and profit data
        $karenderias->getCollection()->transform(function ($karenderia) {
            $karenderia->total_revenue = $karenderia->orders()
                ->where('payment_status', 'paid')
                ->sum('total_amount');
            
            $karenderia->total_profit = $karenderia->orders()
                ->where('payment_status', 'paid')
                ->whereNotNull('total_cost')
                ->selectRaw('SUM(total_amount - total_cost) as profit')
                ->value('profit') ?? 0;

            $karenderia->monthly_revenue = $karenderia->orders()
                ->where('payment_status', 'paid')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_amount');

            return $karenderia;
        });

        return response()->json([
            'success' => true,
            'data' => $karenderias->items(),
            'pagination' => [
                'current_page' => $karenderias->currentPage(),
                'total_pages' => $karenderias->lastPage(),
                'total_items' => $karenderias->total(),
                'per_page' => $karenderias->perPage()
            ]
        ]);
    }

    /**
     * Get detailed karenderia information
     */
    public function karenderiaDetails($id)
    {
        $karenderia = Karenderia::with(['owner', 'menuItems', 'inventory'])
            ->findOrFail($id);

        // Sales data for the last 30 days
        $salesData = $karenderia->orders()
            ->where('payment_status', 'paid')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('
                DATE(created_at) as date,
                COUNT(*) as total_orders,
                SUM(total_amount) as daily_revenue,
                SUM(COALESCE(total_amount - total_cost, 0)) as daily_profit
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Top selling items
        $topItems = MenuItem::where('karenderia_id', $id)
            ->withSum(['orderItems as total_sold' => function($query) {
                $query->whereHas('order', function($subQuery) {
                    $subQuery->where('payment_status', 'paid');
                });
            }], 'quantity')
            ->orderByDesc('total_sold')
            ->take(10)
            ->get();

        // Recent orders
        $recentOrders = Order::where('karenderia_id', $id)
            ->with(['customer:id,name,email', 'orderItems.menuItem:id,name,price'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Inventory alerts
        $lowStockItems = $karenderia->inventory()->lowStock()->count();
        $outOfStockItems = $karenderia->inventory()->outOfStock()->count();
        $expiringItems = $karenderia->inventory()->expiringSoon()->count();

        return response()->json([
            'karenderia' => $karenderia,
            'sales_data' => $salesData,
            'top_selling_items' => $topItems,
            'recent_orders' => $recentOrders,
            'inventory_alerts' => [
                'low_stock' => $lowStockItems,
                'out_of_stock' => $outOfStockItems,
                'expiring_soon' => $expiringItems
            ]
        ]);
    }

    /**
     * Get karenderia inventory
     */
    public function karenderiaInventory($id, Request $request)
    {
        $karenderia = Karenderia::findOrFail($id);
        
        $query = $karenderia->inventory();

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by item name
        if ($request->has('search')) {
            $query->where('item_name', 'LIKE', "%{$request->search}%");
        }

        $inventory = $query->orderBy('item_name')->paginate($request->get('per_page', 20));

        return response()->json($inventory);
    }

    /**
     * Get sales analytics
     */
    public function salesAnalytics(Request $request)
    {
        $startDate = $request->get('start_date', now()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());
        $karenderiaId = $request->get('karenderia_id');

        $query = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($karenderiaId) {
            $query->where('karenderia_id', $karenderiaId);
        }

        // Daily sales data
        $dailySales = $query->selectRaw('
            DATE(created_at) as date,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            SUM(COALESCE(total_amount - total_cost, 0)) as total_profit,
            AVG(total_amount) as average_order_value
        ')
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Top performing karenderias
        $topKarenderias = Karenderia::withSum(['orders as total_revenue' => function($query) use ($startDate, $endDate) {
                $query->where('payment_status', 'paid')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }], 'total_amount')
            ->withCount(['orders as total_orders' => function($query) use ($startDate, $endDate) {
                $query->where('payment_status', 'paid')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->orderByDesc('total_revenue')
            ->take(10)
            ->get();

        // Payment method breakdown
        $paymentMethods = Order::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('payment_method, COUNT(*) as count, SUM(total_amount) as total')
            ->groupBy('payment_method')
            ->get();

        return response()->json([
            'daily_sales' => $dailySales,
            'top_karenderias' => $topKarenderias,
            'payment_methods' => $paymentMethods,
            'summary' => [
                'total_orders' => $query->count(),
                'total_revenue' => $query->sum('total_amount'),
                'total_profit' => $query->whereNotNull('total_cost')
                    ->selectRaw('SUM(total_amount - total_cost) as profit')
                    ->value('profit') ?? 0,
                'average_order_value' => $query->avg('total_amount')
            ]
        ]);
    }

    /**
     * Approve or reject karenderia application
     */
    public function updateKarenderiaStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:approved,active,inactive,pending,rejected',
            'notes' => 'nullable|string'
        ]);

        $karenderia = Karenderia::findOrFail($id);
        $karenderia->status = $request->status;
        $karenderia->save();

        if ($karenderia->owner) {
            if (in_array($request->status, ['approved', 'active'], true)) {
                $karenderia->owner->application_status = 'approved';
                $karenderia->owner->verified = true;
                $karenderia->owner->save();
            } elseif ($request->status === 'pending') {
                $karenderia->owner->application_status = 'pending';
                $karenderia->owner->verified = false;
                $karenderia->owner->save();
            } elseif ($request->status === 'rejected') {
                $karenderia->owner->application_status = 'rejected';
                $karenderia->owner->verified = false;
                $karenderia->owner->save();
            }
        }

        // You can add notification logic here to inform the owner

        return response()->json([
            'message' => 'Karenderia status updated successfully',
            'karenderia' => $karenderia
        ]);
    }

    /**
     * Get inventory alerts across all karenderias
     */
    public function inventoryAlerts()
    {
        $lowStockItems = Inventory::with(['karenderia:id,name'])
            ->lowStock()
            ->orderBy('current_stock', 'asc')
            ->take(50)
            ->get();

        $outOfStockItems = Inventory::with(['karenderia:id,name'])
            ->outOfStock()
            ->orderBy('current_stock', 'asc')
            ->take(50)
            ->get();

        $expiringItems = Inventory::with(['karenderia:id,name'])
            ->expiringSoon()
            ->orderBy('expiry_date', 'asc')
            ->take(50)
            ->get();

        $expiredItems = Inventory::with(['karenderia:id,name'])
            ->expired()
            ->orderBy('expiry_date', 'asc')
            ->take(50)
            ->get();

        return response()->json([
            'low_stock' => $lowStockItems,
            'out_of_stock' => $outOfStockItems,
            'expiring_soon' => $expiringItems,
            'expired' => $expiredItems
        ]);
    }

    /**
     * Get all users (customers and owners)
     */
    public function users(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->withCount(['mealPlans', 'karenderia'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($users);
    }

    /**
     * Get all menu items across all karenderias for admin inventory view
     */
    public function allMenuItems(Request $request)
    {
        $query = MenuItem::with(['karenderia']);

        // Filter by karenderia if specified
        if ($request->has('karenderia_id')) {
            $query->where('karenderia_id', $request->karenderia_id);
        }

        // Filter by availability
        if ($request->has('available')) {
            $query->where('is_available', $request->boolean('available'));
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $menuItems = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $menuItems,
            'message' => 'Menu items retrieved successfully'
        ]);
    }

    /**
     * Get all karenderias with owner information
     */
    public function getAllKarenderias(Request $request)
    {
        $query = Karenderia::with(['owner:id,name,email']);

        // Filter by status if specified
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $karenderias = $query->orderBy('created_at', 'desc')->get();

        // Transform the data to include owner information
        $transformedKarenderias = $karenderias->map(function ($karenderia) {
            return [
                'id' => $karenderia->id,
                'business_name' => $karenderia->business_name,
                'description' => $karenderia->description,
                'address' => $karenderia->address,
                'city' => $karenderia->city,
                'province' => $karenderia->province,
                'latitude' => $karenderia->latitude,
                'longitude' => $karenderia->longitude,
                'phone' => $karenderia->phone,
                'business_email' => $karenderia->business_email,
                'opening_time' => $karenderia->opening_time,
                'closing_time' => $karenderia->closing_time,
                'operating_days' => json_decode($karenderia->operating_days, true),
                'delivery_fee' => $karenderia->delivery_fee,
                'delivery_time_minutes' => $karenderia->delivery_time_minutes,
                'accepts_cash' => $karenderia->accepts_cash,
                'accepts_online_payment' => $karenderia->accepts_online_payment,
                'status' => $karenderia->status,
                'approved_at' => $karenderia->approved_at,
                'approved_by' => $karenderia->approved_by,
                'owner_id' => $karenderia->owner_id,
                'owner_name' => $karenderia->owner->name,
                'owner_email' => $karenderia->owner->email,
                'created_at' => $karenderia->created_at,
                'updated_at' => $karenderia->updated_at,
            ];
        });

        return response()->json($transformedKarenderias);
    }

    /**
     * Update karenderia location (latitude/longitude)
     */
    public function updateKarenderiaLocation(Request $request, $id)
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180'
        ]);

        $karenderia = Karenderia::findOrFail($id);
        
        $karenderia->update([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Karenderia location updated successfully',
            'data' => $karenderia
        ]);
    }

    /**
     * Update karenderia details
     */
    public function updateKarenderiaDetails(Request $request, $id)
    {
        $karenderia = Karenderia::findOrFail($id);
        
        $validatedData = $request->validate([
            'business_name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'address' => 'sometimes|string',
            'city' => 'sometimes|string|max:100',
            'province' => 'sometimes|string|max:100',
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

        // Convert operating_days to JSON if provided
        if (isset($validatedData['operating_days'])) {
            $validatedData['operating_days'] = json_encode($validatedData['operating_days']);
        }

        $karenderia->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Karenderia details updated successfully',
            'data' => $karenderia
        ]);
    }

    /**
     * Delete karenderia
     */
    public function deleteKarenderia($id)
    {
        $karenderia = Karenderia::findOrFail($id);
        $karenderia->delete();

        return response()->json([
            'success' => true,
            'message' => 'Karenderia deleted successfully'
        ]);
    }

    /**
     * Get karenderia by ID with owner information
     */
    public function getKarenderiaById($id)
    {
        $karenderia = Karenderia::with(['owner:id,name,email'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $karenderia->id,
                'business_name' => $karenderia->business_name,
                'description' => $karenderia->description,
                'address' => $karenderia->address,
                'city' => $karenderia->city,
                'province' => $karenderia->province,
                'latitude' => $karenderia->latitude,
                'longitude' => $karenderia->longitude,
                'phone' => $karenderia->phone,
                'business_email' => $karenderia->business_email,
                'opening_time' => $karenderia->opening_time,
                'closing_time' => $karenderia->closing_time,
                'operating_days' => json_decode($karenderia->operating_days, true),
                'delivery_fee' => $karenderia->delivery_fee,
                'delivery_time_minutes' => $karenderia->delivery_time_minutes,
                'accepts_cash' => $karenderia->accepts_cash,
                'accepts_online_payment' => $karenderia->accepts_online_payment,
                'status' => $karenderia->status,
                'approved_at' => $karenderia->approved_at,
                'approved_by' => $karenderia->approved_by,
                'owner_id' => $karenderia->owner_id,
                'owner_name' => $karenderia->owner->name,
                'owner_email' => $karenderia->owner->email,
                'created_at' => $karenderia->created_at,
                'updated_at' => $karenderia->updated_at,
            ]
        ]);
    }

    /**
     * Get dashboard stats
     */
    public function getDashboardStats()
    {
        $pendingKarenderias = Karenderia::where('status', 'pending')->count();
        $pendingSuppliers = User::where('role', 'supplier')
            ->where('application_status', 'pending')
            ->count();

        $stats = [
            'total_karenderias' => Karenderia::count(),
            'pending_karenderias' => $pendingKarenderias,
            'pending_suppliers' => $pendingSuppliers,
            'pending_approvals' => $pendingKarenderias + $pendingSuppliers,
            'approved_karenderias' => Karenderia::where('status', 'approved')->count(),
            'active_karenderias' => Karenderia::where('status', 'active')->count(),
            'karenderias_without_location' => Karenderia::whereNull('latitude')->orWhereNull('longitude')->count(),
            'total_users' => User::where('role', 'customer')->count(),
            'total_owners' => User::where('role', 'karenderia_owner')->count(),
            'total_suppliers' => User::where('role', 'supplier')->count(),
            'total_orders' => Order::count(),
            'todays_orders' => Order::whereDate('created_at', today())->count(),
            'total_revenue' => Order::where('payment_status', 'paid')->sum('total_amount')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get all customers
     */
    public function getCustomers()
    {
        $customers = User::where('role', 'customer')
            ->with(['orders' => function($query) {
                $query->select('user_id', 'total_amount', 'payment_status', 'created_at')
                      ->orderBy('created_at', 'desc')
                      ->limit(5);
            }])
            ->withCount(['orders'])
            ->withSum('orders', 'total_amount')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'verified' => $customer->verified,
                    'application_status' => $customer->application_status,
                    'created_at' => $customer->created_at,
                    'last_login' => $customer->updated_at,
                    'total_orders' => $customer->orders_count,
                    'total_spent' => $customer->orders_sum_total_amount ?? 0,
                    'recent_orders' => $customer->orders,
                    'status' => $customer->verified ? 'verified' : 'unverified'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Get all karenderia owners
     */
    public function getKarenderiaOwners()
    {
        $owners = User::where('role', 'karenderia_owner')
            ->with(['karenderia' => function($query) {
                $query->select('user_id', 'business_name', 'status', 'city', 'province', 'approved_at', 'latitude', 'longitude');
            }])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($owner) {
                return [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'email' => $owner->email,
                    'phone' => $owner->phone,
                    'verified' => $owner->verified,
                    'application_status' => $owner->application_status,
                    'created_at' => $owner->created_at,
                    'karenderia' => $owner->karenderia ? [
                        'business_name' => $owner->karenderia->business_name,
                        'status' => $owner->karenderia->status,
                        'location' => $owner->karenderia->city . ', ' . $owner->karenderia->province,
                        'approved_at' => $owner->karenderia->approved_at,
                        'has_location' => !is_null($owner->karenderia->latitude) && !is_null($owner->karenderia->longitude)
                    ] : null,
                    'status' => $owner->verified ? 'verified' : ($owner->application_status ?? 'unverified')
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $owners
        ]);
    }

    /**
     * Get all supplier accounts
     */
    public function getSuppliers()
    {
        $suppliers = User::where('role', 'supplier')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($supplier) {
                return [
                    'id' => $supplier->id,
                    'name' => $supplier->name,
                    'email' => $supplier->email,
                    'phone_number' => $supplier->phone_number,
                    'address' => $supplier->address,
                    'application_status' => $supplier->application_status,
                    'verified' => $supplier->verified,
                    'status' => $supplier->verified ? 'verified' : $supplier->application_status,
                    'created_at' => $supplier->created_at,
                    'updated_at' => $supplier->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    /**
     * Update supplier application status
     */
    public function updateSupplierApplicationStatus(Request $request, $userId)
    {
        $request->validate([
            'application_status' => 'required|in:pending,approved,rejected'
        ]);

        $user = User::findOrFail($userId);

        if ($user->role !== 'supplier') {
            return response()->json([
                'success' => false,
                'message' => 'Target user is not a supplier'
            ], 422);
        }

        $user->application_status = $request->application_status;
        $user->verified = $request->application_status === 'approved';
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Supplier application status updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Update user role
     */
    public function updateUserRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|in:customer,karenderia_owner,supplier,admin'
        ]);

        $user = User::findOrFail($userId);
        
        // Prevent changing admin role unless current user is admin
        if ($user->role === 'admin' && auth()->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify admin user role'
            ], 403);
        }

        $oldRole = $user->role;
        $user->role = $request->role;

        if (in_array($request->role, ['karenderia_owner', 'supplier'], true)) {
            $user->application_status = 'pending';
            $user->verified = false;
        } else {
            $user->application_status = 'approved';
            $user->verified = true;
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => "User role updated from {$oldRole} to {$request->role}",
            'data' => $user
        ]);
    }

    /**
     * Toggle user status (enable/disable)
     */
    public function toggleUserStatus($userId)
    {
        $user = User::findOrFail($userId);
        
        // Prevent disabling admin users
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot disable admin users'
            ], 403);
        }

        // Toggle email_verified_at as a way to enable/disable users
        $user->email_verified_at = $user->email_verified_at ? null : now();
        $user->save();

        $status = $user->email_verified_at ? 'enabled' : 'disabled';

        return response()->json([
            'success' => true,
            'message' => "User has been {$status}",
            'data' => $user
        ]);
    }

    /**
     * Delete user (soft delete or hard delete)
     */
    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        
        // Prevent deleting admin users
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete admin users'
            ], 403);
        }

        // If karenderia owner, also handle karenderia record
        if ($user->role === 'karenderia_owner' && $user->karenderia) {
            $user->karenderia->delete();
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
