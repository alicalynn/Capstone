<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInventoryItem extends Model
{
    protected $fillable = [
        'supplier_id',
        'item_name',
        'description',
        'category',
        'unit',
        'price_per_unit',
        'available_stock',
        'minimum_order_quantity',
        'is_active',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'available_stock' => 'decimal:3',
        'minimum_order_quantity' => 'decimal:3',
        'is_active' => 'boolean',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(SupplyOrderItem::class);
    }
}
