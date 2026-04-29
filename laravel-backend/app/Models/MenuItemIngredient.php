<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItemIngredient extends Model
{
    protected $table = 'menu_item_ingredients';

    protected $fillable = [
        'menu_item_id',
        'inventory_id',
        'quantity_needed'
    ];

    protected $casts = [
        'quantity_needed' => 'decimal:3'
    ];

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }
}
