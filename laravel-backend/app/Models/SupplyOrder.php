<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplyOrder extends Model
{
    protected $fillable = [
        'karenderia_id',
        'supplier_id',
        'status',
        'payment_status',
        'payment_date',
        'payment_method',
        'payment_reference',
        'total_amount',
        'notes',
        'delivery_date',
        'delivery_method',
        'delivery_address',
        'delivery_coordinates',
        'confirmed_at',
        'shipped_at',
        'out_for_delivery_at',
        'delivered_at',
        'delivery_notes',
        'delivered_by_name',
        'delivery_signature_url',
        'photo_proof_urls',
        'status_history',
        'failed_reason',
        'retry_count',
        'max_retries',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'delivery_date' => 'date',
        'payment_date' => 'datetime',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'out_for_delivery_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_coordinates' => 'json',
        'photo_proof_urls' => 'json',
        'status_history' => 'json',
    ];

    public function karenderia(): BelongsTo
    {
        return $this->belongsTo(Karenderia::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplyOrderItem::class);
    }

    /**
     * Get the timeline of all status changes for this order
     */
    public function getStatusTimeline(): array
    {
        $statuses = [
            ['status' => 'pending', 'label' => 'Order Placed', 'timestamp' => $this->created_at],
            ['status' => 'confirmed', 'label' => 'Supplier Confirmed', 'timestamp' => $this->confirmed_at],
            ['status' => 'payment_confirmed', 'label' => 'Payment Confirmed', 'timestamp' => $this->payment_date],
            ['status' => 'preparing', 'label' => 'Being Prepared', 'timestamp' => null],
            ['status' => 'shipped', 'label' => 'Shipped', 'timestamp' => $this->shipped_at],
            ['status' => 'in_transit', 'label' => 'In Transit', 'timestamp' => null],
            ['status' => 'out_for_delivery', 'label' => 'Out for Delivery', 'timestamp' => $this->out_for_delivery_at],
            ['status' => 'delivering', 'label' => 'Delivering', 'timestamp' => $this->out_for_delivery_at],
            ['status' => 'delivered', 'label' => 'Delivered', 'timestamp' => $this->delivered_at],
        ];

        return collect($statuses)
            ->map(function ($item) {
                $item['completed'] = $item['timestamp'] !== null && $this->isStatusCompleted($item['status']);
                $item['current'] = $this->status === $item['status'];
                return $item;
            })
            ->toArray();
    }

    /**
     * Check if a status has been completed
     */
    public function isStatusCompleted(string $status): bool
    {
        $statusOrder = [
            'pending' => 0,
            'confirmed' => 1,
            'payment_confirmed' => 2,
            'preparing' => 3,
            'shipped' => 4,
            'in_transit' => 5,
            'out_for_delivery' => 6,
            'delivering' => 7,
            'delivered' => 8,
        ];

        $currentStatusValue = $statusOrder[$this->status] ?? -1;
        $checkStatusValue = $statusOrder[$status] ?? -1;

        return $checkStatusValue < $currentStatusValue;
    }

    /**
     * Get possible next statuses for this order
     */
    public function getNextPossibleStatuses(): array
    {
        $currentStatus = $this->status;

        $transitions = [
            'pending' => ['confirmed', 'cancelled'],
            'confirmed' => ['delivering', 'payment_confirmed', 'cancelled'],
            'payment_confirmed' => ['preparing', 'cancelled'],
            'preparing' => ['shipped', 'cancelled'],
            'shipped' => ['in_transit', 'delivery_failed'],
            'in_transit' => ['out_for_delivery', 'delivery_failed'],
            'out_for_delivery' => ['delivering', 'delivery_failed'],
            'delivering' => ['delivered', 'delivery_failed'],
            'delivered' => [],
            'delivery_failed' => $this->canBeRetried() ? ['out_for_delivery'] : ['cancelled'],
            'cancelled' => [],
        ];

        return $transitions[$currentStatus] ?? [];
    }

    /**
     * Check if order can be retried after delivery failure
     */
    public function canBeRetried(): bool
    {
        return $this->status === 'delivery_failed' 
            && $this->retry_count < $this->max_retries;
    }

    /**
     * Check if order is in a terminal state (no further changes possible)
     */
    public function isTerminal(): bool
    {
        return in_array($this->status, ['delivered', 'cancelled']);
    }

    /**
     * Check if order is ready for stock sync (delivered with proof)
     */
    public function isReadyForStockSync(): bool
    {
        return $this->status === 'delivered' 
            && $this->delivered_at !== null;
    }

    /**
     * Record a status change in the history
     */
    public function recordStatusChange(string $newStatus, ?string $reason = null): void
    {
        $history = $this->status_history ?? [];
        
        $history[] = [
            'from_status' => $this->status,
            'to_status' => $newStatus,
            'changed_at' => now()->toIso8601String(),
            'reason' => $reason,
        ];

        $this->update(['status_history' => $history]);
    }
}
