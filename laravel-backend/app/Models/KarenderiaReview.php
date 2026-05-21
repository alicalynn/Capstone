<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KarenderiaReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'karenderia_id',
        'reviewer_id',
        'reviewer_type',
        'rating',
        'comment',
        'karenderia_status',
        'status',
        'moderation_note',
        'food_feedback',
        'food_quality_rating',
        'delivery_experience_rating',
        'tags',
        'reviewed_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'reviewed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function karenderia(): BelongsTo
    {
        return $this->belongsTo(Karenderia::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Scope to get only approved reviews
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope to get reviews for a karenderia
     */
    public function scopeForKarenderia($query, $karenderiaId)
    {
        return $query->where('karenderia_id', $karenderiaId);
    }

    /**
     * Get average rating for a karenderia
     */
    public static function getAverageRating($karenderiaId)
    {
        return self::approved()
            ->forKarenderia($karenderiaId)
            ->avg('rating') ?? 0;
    }

    /**
     * Get rating statistics
     */
    public static function getRatingStats($karenderiaId)
    {
        $reviews = self::approved()
            ->forKarenderia($karenderiaId)
            ->get();

        $stats = [
            'average' => 0,
            'total_reviews' => 0,
            'distribution' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
            'status_breakdown' => [],
        ];

        if ($reviews->isEmpty()) {
            return $stats;
        }

        $stats['total_reviews'] = $reviews->count();
        $stats['average'] = round($reviews->avg('rating'), 1);

        // Distribution
        foreach ($reviews as $review) {
            $stats['distribution'][$review->rating]++;
        }

        // Status breakdown
        foreach ($reviews->groupBy('karenderia_status') as $status => $statusReviews) {
            $stats['status_breakdown'][$status] = $statusReviews->count();
        }

        return $stats;
    }
}
