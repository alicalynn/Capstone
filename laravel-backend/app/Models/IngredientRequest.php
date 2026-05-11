<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IngredientRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'karenderia_id',
        'title',
        'description',
        'ingredient_type',
        'needed_quantity',
        'unit',
        'needed_by_date',
        'budget',
        'status',
        'accepted_supplier_id',
        'location_coordinates',
        'delivery_address',
        'expiry_hours',
    ];

    protected $casts = [
        'needed_quantity' => 'decimal:2',
        'budget' => 'decimal:2',
        'needed_by_date' => 'date',
        'location_coordinates' => 'array',
    ];

    public function karenderia(): BelongsTo
    {
        return $this->belongsTo(Karenderia::class);
    }

    public function acceptedSupplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_supplier_id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(SupplierQuote::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function acceptedQuote()
    {
        return $this->quotes()->where('status', 'accepted')->first();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isExpired(): bool
    {
        return $this->created_at->addHours($this->expiry_hours)->isPast();
    }
}
