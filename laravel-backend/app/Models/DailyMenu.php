<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMenu extends Model
{
    protected $fillable = [
        'karenderia_id',
        'menu_item_id',
        'inventory_id',
        'date',
        'meal_type',
        'quantity',
        'original_quantity',
        'ingredient_quantity',
        'is_available',
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'original_quantity' => 'integer',
        'ingredient_quantity' => 'decimal:3',
        'is_available' => 'boolean',
        // 'special_price' removed - deprecated
    ];

    /**
     * Get the karenderia that owns this daily menu entry
     */
    public function karenderia(): BelongsTo
    {
        return $this->belongsTo(Karenderia::class);
    }

    /**
     * Get the menu item for this daily menu entry
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Get the inventory item associated with this daily menu entry
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * Scope to get daily menu for a specific date
     */
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope to get daily menu for a specific meal type
     */
    public function scopeForMealType($query, $mealType)
    {
        return $query->where('meal_type', $mealType);
    }

    /**
     * Scope to get only available items
     */
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)->where('quantity', '>', 0);
    }

    /**
     * Check if there are enough servings available
     */
    public function hasEnoughServings($requestedQuantity)
    {
        return $this->is_available && $this->quantity >= $requestedQuantity;
    }

    /**
     * Reduce the available quantity (when someone orders)
     */
    public function reduceQuantity($amount)
    {
        if ($this->quantity >= $amount) {
            $this->quantity -= $amount;
            $this->save();
            return true;
        }
        return false;
    }
}
