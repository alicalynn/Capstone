<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Karenderia extends Model
{
    protected $fillable = [
        'name',
        'business_name',
        'description',
        'address',
        'city',
        'province',
        'phone',
        'email',
        'business_email',
        'owner_id',
        'latitude',
        'longitude',
        'opening_time',
        'closing_time',
        'operating_days',
        'status',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejection_reason',
        'reapplication_count',
        'admin_notes',
        'business_permit',
        'logo_url',
        'images',
        'average_rating',
        'total_reviews',
        'delivery_fee',
        'delivery_time_minutes',
        'accepts_cash',
        'accepts_online_payment'
    ];

    protected $casts = [
        'operating_days' => 'array',
        'images' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'average_rating' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'accepts_cash' => 'boolean',
        'accepts_online_payment' => 'boolean',
        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    public function inventory(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function dailyMenus(): HasMany
    {
        return $this->hasMany(DailyMenu::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(KarenderiaReview::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(KarenderiaReport::class);
    }

    // Scope for active karenderias
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Calculate total revenue
    public function getTotalRevenueAttribute()
    {
        return $this->orders()
            ->where('payment_status', 'paid')
            ->sum('total_amount');
    }

    // Calculate total profit
    public function getTotalProfitAttribute()
    {
        return $this->orders()
            ->where('payment_status', 'paid')
            ->whereNotNull('total_cost')
            ->selectRaw('SUM(total_amount - total_cost) as profit')
            ->value('profit') ?? 0;
    }

    // Get monthly sales data
    public function getMonthlySales($year = null, $month = null)
    {
        $query = $this->orders()
            ->where('payment_status', 'paid');

        if ($year) {
            $query->whereYear('created_at', $year);
        }

        if ($month) {
            $query->whereMonth('created_at', $month);
        }

        return $query->selectRaw('
            DATE_FORMAT(created_at, "%Y-%m-%d") as date,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_revenue,
            SUM(COALESCE(total_amount - total_cost, 0)) as total_profit
        ')
        ->groupBy('date')
        ->orderBy('date')
        ->get();
    }
}