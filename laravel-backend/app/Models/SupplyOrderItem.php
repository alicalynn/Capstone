<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyOrderItem extends Model
{
    protected $fillable = [
        'supply_order_id',
        'supplier_inventory_item_id',
        'quantity',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SupplyOrder::class, 'supply_order_id');
    }

    public function supplierItem(): BelongsTo
    {
        return $this->belongsTo(SupplierInventoryItem::class, 'supplier_inventory_item_id');
    }
}
