<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KarenderiaReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'karenderia_id',
        'reporter_id',
        'reporter_type',
        'report_type',
        'description',
        'evidence',
        'attachments',
        'status',
        'admin_response',
        'assigned_admin_id',
        'resolved_at',
        'verified',
        'similar_reports_count',
    ];

    protected $casts = [
        'attachments' => 'array',
        'verified' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function karenderia(): BelongsTo
    {
        return $this->belongsTo(Karenderia::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    /**
     * Scope for active/unresolved reports
     */
    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['new', 'under_review', 'acknowledged']);
    }

    /**
     * Scope for reports of a specific type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('report_type', $type);
    }

    /**
     * Scope for verified serious reports
     */
    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    /**
     * Get reports for a karenderia
     */
    public function scopeForKarenderia($query, $karenderiaId)
    {
        return $query->where('karenderia_id', $karenderiaId);
    }

    /**
     * Check if this is a serious issue
     */
    public function isSeriousIssue(): bool
    {
        $seriousTypes = [
            'permanent_closure',
            'allergy_issue',
            'food_safety',
            'health_violation'
        ];
        return in_array($this->report_type, $seriousTypes);
    }

    /**
     * Mark as resolved
     */
    public function markResolved($adminResponse = null)
    {
        $this->update([
            'status' => 'resolved',
            'admin_response' => $adminResponse,
            'resolved_at' => now(),
        ]);
    }
}
