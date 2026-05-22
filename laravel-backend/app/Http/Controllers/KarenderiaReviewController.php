<?php

namespace App\Http\Controllers;

use App\Models\Karenderia;
use App\Models\KarenderiaReview;
use App\Models\KarenderiaReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class KarenderiaReviewController extends Controller
{
    /**
     * Get reviews for a karenderia
     */
    public function getReviews(Request $request, int $karenderiaId): JsonResponse
    {
        $karenderia = Karenderia::findOrFail($karenderiaId);

        $reviews = KarenderiaReview::approved()
            ->forKarenderia($karenderiaId)
            ->with(['reviewer:id,name,email'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $stats = KarenderiaReview::getRatingStats($karenderiaId);

        return response()->json([
            'data' => [
                'karenderia' => [
                    'id' => $karenderia->id,
                    'name' => $karenderia->business_name ?: $karenderia->name,
                ],
                'stats' => $stats,
                'reviews' => $reviews,
            ]
        ]);
    }

    /**
     * Create a review for a karenderia
     */
    public function createReview(Request $request, int $karenderiaId): JsonResponse
    {
        $user = $request->user();
        $karenderia = Karenderia::findOrFail($karenderiaId);

        // Validate request
        $validated = $request->validate([
            'rating' => 'required|integer|between:1,5',
            'comment' => 'sometimes|string|max:2000',
            'karenderia_status' => 'required|in:open,closed_temporary,closed_permanent,unknown',
            'food_feedback' => 'sometimes|string|max:1000',
            'food_quality_rating' => 'sometimes|integer|between:1,5',
            'delivery_experience_rating' => 'sometimes|integer|between:1,5',
            'tags' => 'sometimes|array|max:5',
        ]);

        try {
            // Check if user already reviewed this karenderia
            $existingReview = KarenderiaReview::where('karenderia_id', $karenderiaId)
                ->where('reviewer_id', $user->id)
                ->exists();

            if ($existingReview) {
                return response()->json([
                    'error' => 'You have already reviewed this karenderia'
                ], 422);
            }

            // Create review
            $review = KarenderiaReview::create([
                'karenderia_id' => $karenderiaId,
                'reviewer_id' => $user->id,
                'reviewer_type' => $user->role,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'karenderia_status' => $validated['karenderia_status'],
                'food_feedback' => $validated['food_feedback'] ?? null,
                'food_quality_rating' => $validated['food_quality_rating'] ?? null,
                'delivery_experience_rating' => $validated['delivery_experience_rating'] ?? null,
                'tags' => $validated['tags'] ?? null,
                'reviewed_at' => now(),
                'status' => 'pending', // Requires moderation
            ]);

            // Check for serious status reports
            if ($validated['karenderia_status'] === 'closed_permanent') {
                // Auto-create a report for permanent closure
                $this->createAutoReport(
                    $karenderia,
                    $user,
                    'permanent_closure',
                    'Reported via review: ' . ($validated['comment'] ?? 'No details provided')
                );
            }

            Log::info("Karenderia review submitted", [
                'karenderia_id' => $karenderiaId,
                'reviewer_id' => $user->id,
                'rating' => $validated['rating'],
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Review submitted successfully. It will be approved by our team.',
                'data' => [
                    'review' => $review,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error creating karenderia review", [
                'karenderia_id' => $karenderiaId,
                'reviewer_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to submit review'
            ], 500);
        }
    }

    /**
     * Report a serious karenderia issue (allergy, closure, health violation, etc)
     */
    public function reportIssue(Request $request, int $karenderiaId): JsonResponse
    {
        $user = $request->user();
        $karenderia = Karenderia::findOrFail($karenderiaId);

        $validated = $request->validate([
            'report_type' => 'required|in:permanent_closure,temporary_closure,allergy_issue,food_safety,health_violation,quality_issue,other',
            'description' => 'required|string|max:3000|min:10',
            'evidence' => 'sometimes|string|max:1000',
            'attachments' => 'sometimes|array|max:3',
            'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120',
        ]);

        try {
            $attachmentUrls = [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    if ($attachment && $attachment->isValid()) {
                        $path = $attachment->store('karenderia-report-attachments', 'public');
                        $attachmentUrls[] = Storage::url($path);
                    }
                }
            }

            // Create report
            $report = KarenderiaReport::create([
                'karenderia_id' => $karenderiaId,
                'reporter_id' => $user->id,
                'reporter_type' => $user->role,
                'report_type' => $validated['report_type'],
                'description' => $validated['description'],
                'evidence' => $validated['evidence'] ?? null,
                'attachments' => !empty($attachmentUrls) ? $attachmentUrls : null,
                'status' => 'new',
                'verified' => false,
            ]);

            // Check for similar reports from other users
            $similarReports = KarenderiaReport::forKarenderia($karenderiaId)
                ->byType($validated['report_type'])
                ->where('reporter_id', '!=', $user->id)
                ->where('created_at', '>', now()->subDays(7))
                ->count();

            $report->update(['similar_reports_count' => $similarReports]);

            // If multiple serious reports of the same type, flag for review
            if ($similarReports >= 2) {
                $report->update(['status' => 'under_review']);
                Log::warning("Multiple serious reports for karenderia", [
                    'karenderia_id' => $karenderiaId,
                    'report_type' => $validated['report_type'],
                    'count' => $similarReports + 1,
                ]);
            }

            Log::info("Karenderia issue reported", [
                'karenderia_id' => $karenderiaId,
                'reporter_id' => $user->id,
                'report_type' => $validated['report_type'],
                'timestamp' => now(),
            ]);

            return response()->json([
                'message' => 'Report submitted. Our team will review it and take appropriate action.',
                'data' => [
                    'report' => $report,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error creating karenderia report", [
                'karenderia_id' => $karenderiaId,
                'reporter_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to submit report'
            ], 500);
        }
    }

    /**
     * Get pending reviews for moderation (admin only)
     */
    public function getPendingReviews(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $reviews = KarenderiaReview::where('status', 'pending')
            ->with(['karenderia:id,business_name,name', 'reviewer:id,name,email'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return response()->json([
            'data' => $reviews
        ]);
    }

    /**
     * Approve or reject a review (admin only)
     */
    public function moderateReview(Request $request, int $reviewId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $review = KarenderiaReview::findOrFail($reviewId);

        $validated = $request->validate([
            'action' => 'required|in:approve,reject',
            'moderation_note' => 'sometimes|string|max:500',
        ]);

        try {
            $review->update([
                'status' => $validated['action'] === 'approve' ? 'approved' : 'rejected',
                'moderation_note' => $validated['moderation_note'] ?? null,
            ]);

            Log::info("Review moderated by admin", [
                'review_id' => $reviewId,
                'action' => $validated['action'],
                'admin_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Review moderated successfully',
                'data' => ['review' => $review]
            ]);

        } catch (\Exception $e) {
            Log::error("Error moderating review", [
                'review_id' => $reviewId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to moderate review'
            ], 500);
        }
    }

    /**
     * Get reports for admin review
     */
    public function getReports(Request $request, $karenderiaId = null): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = KarenderiaReport::unresolved()
            ->with(['karenderia:id,business_name,name', 'reporter:id,name,email'])
            ->orderBy('verified', 'desc')
            ->orderBy('similar_reports_count', 'desc')
            ->orderBy('created_at', 'asc');

        if ($karenderiaId) {
            $query->forKarenderia($karenderiaId);
        }

        $reports = $query->paginate(20);

        return response()->json([
            'data' => $reports
        ]);
    }

    /**
     * Helper: Auto-create a report when user reports closure in review
     */
    private function createAutoReport(Karenderia $karenderia, $reporter, $type, $description): void
    {
        try {
            KarenderiaReport::create([
                'karenderia_id' => $karenderia->id,
                'reporter_id' => $reporter->id,
                'reporter_type' => $reporter->role,
                'report_type' => $type,
                'description' => $description,
                'status' => 'new',
                'verified' => false,
            ]);
        } catch (\Exception $e) {
            Log::error("Error creating auto-report", [
                'karenderia_id' => $karenderia->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
