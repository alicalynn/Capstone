<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierQuote extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ingredient_request_id',
        'supplier_id',
        'price_per_unit',
        'total_price',
        'available_quantity',
        'unit',
        'notes',
        'delivery_date',
        'delivery_method',
        'status',
        'responded_at',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
        'total_price' => 'decimal:2',
        'available_quantity' => 'decimal:2',
        'delivery_date' => 'date',
        'responded_at' => 'datetime',
    ];

    public function ingredientRequest(): BelongsTo
    {
        return $this->belongsTo(IngredientRequest::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function accept()
    {
        $this->status = 'accepted';
        $this->responded_at = now();
        $this->save();

        // Update the ingredient request
        $this->ingredientRequest()->update([
            'status' => 'accepted',
            'accepted_supplier_id' => $this->supplier_id,
        ]);

        // Reject all other quotes
        $this->ingredientRequest->quotes()
            ->where('id', '!=', $this->id)
            ->where('status', 'pending')
            ->update(['status' => 'rejected']);
    }

    public function reject()
    {
        $this->status = 'rejected';
        $this->responded_at = now();
        $this->save();
    }
}
