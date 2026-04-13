<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KarenderiaSupplierSuki extends Model
{
    protected $fillable = [
        'karenderia_id',
        'supplier_id',
    ];

    public function karenderia(): BelongsTo
    {
        return $this->belongsTo(Karenderia::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }
}
