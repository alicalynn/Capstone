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
        try {
            $user = $request->user();
            $karenderia = Karenderia::findOrFail($karenderiaId);

            // Validate request
            $validated = $request->validate([
                'rating' => 'required|integer|between:1,5',
                'comment' => 'required|string|min:10|max:2000',
                'karenderia_status' => 'nullable|in:open,closed_temporary,closed_permanent,unknown',
                'food_feedback' => 'nullable|string|max:1000',
                'food_quality_rating' => 'nullable|integer|between:1,5',
                'delivery_experience_rating' => 'nullable|integer|between:1,5',
                'tags' => 'nullable|array|max:5',
                'tags.*' => 'string|max:100',
            ]);

            // Check if user already reviewed this karenderia
            $existingReview = KarenderiaReview::where('karenderia_id', $karenderiaId)
                ->where('reviewer_id', $user->id)
                ->exists();

            if ($existingReview) {
                $karenderiaName = $karenderia->business_name ?: $karenderia->name;
                return response()->json([
                    'error' => "You have already reviewed {$karenderiaName}. You can only submit one review per karenderia."
                ], 422);
            }

            // Ensure tags are properly formatted as array of strings
            $tags = null;
            if (!empty($validated['tags']) && is_array($validated['tags'])) {
                // Convert tags to array of strings and filter out empty values
                $tags = array_filter(array_map('strval', $validated['tags']));
                $tags = !empty($tags) ? array_values($tags) : null; // Re-index array
            }

            // Create review
            $review = KarenderiaReview::create([
                'karenderia_id' => $karenderiaId,
                'reviewer_id' => $user->id,
                'reviewer_type' => $user->role,
                'rating' => $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'karenderia_status' => $validated['karenderia_status'] ?? 'unknown',
                'food_feedback' => $validated['food_feedback'] ?? null,
                'food_quality_rating' => $validated['food_quality_rating'] ?? null,
                'delivery_experience_rating' => $validated['delivery_experience_rating'] ?? null,
                'tags' => $tags,
                'reviewed_at' => now(),
                'status' => 'pending', // Requires moderation
            ]);

            // Check for serious status reports (only if explicitly marked as permanently closed)
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
                'tags' => $review->tags,
                'timestamp' => now(),
            ]);

            // Return minimal response to avoid JSON encoding issues with tags
            return response()->json([
                'message' => 'Review submitted successfully. It will be approved by our team.',
                'data' => [
                    'id' => $review->id,
                    'karenderia_id' => $review->karenderia_id,
                    'rating' => $review->rating,
                    'status' => $review->status,
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors with field details
            Log::warning("Review validation error", [
                'karenderia_id' => $karenderiaId,
                'errors' => $e->validator->errors()->messages()
            ]);
            return response()->json([
                'error' => 'Validation failed. Please check the following:',
                'errors' => $e->validator->errors()->messages()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error creating karenderia review", [
                'karenderia_id' => $karenderiaId,
                'reviewer_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to submit review',
                'message' => $e->getMessage(),
                'debug' => config('app.debug') ? [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ] : null
            ], 500);
        }
    }

    /**
     * Report a serious karenderia issue (allergy, closure, health violation, etc)
     */
    public function reportIssue(Request $request, int $karenderiaId): JsonResponse
    {
        try {
            $user = $request->user();
            $karenderia = Karenderia::findOrFail($karenderiaId);

            $validated = $request->validate([
                'report_type' => 'required|in:permanent_closure,temporary_closure,allergy_issue,food_safety,health_violation,quality_issue,other',
                'description' => 'required|string|max:3000|min:10',
                'evidence' => 'sometimes|string|max:1000',
                'attachments' => 'sometimes|array|max:3',
                'attachments.*' => 'file|mimes:jpg,jpeg,png,gif,webp,pdf|max:5120',
            ]);

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

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Return validation errors with field details
            return response()->json([
                'error' => 'Validation failed. Please check the following:',
                'errors' => $e->validator->errors()->messages()
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error creating karenderia report", [
                'karenderia_id' => $karenderiaId,
                'reporter_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to submit report',
                'message' => $e->getMessage(),
                'debug' => config('app.debug') ? $e->getTraceAsString() : null
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
     * Admin: Investigate and respond to a report
     */
    public function investigateReport(Request $request, int $reportId): JsonResponse
    {
        $user = $request->user();

        if ($user->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $report = KarenderiaReport::findOrFail($reportId);

        $validated = $request->validate([
            'status' => 'required|in:new,under_review,acknowledged,resolved,rejected',
            'admin_response' => 'required|string|min:10|max:2000',
            'verified' => 'sometimes|boolean',
            'action_taken' => 'sometimes|in:none,warning,suspension,permanent_closure',
        ]);

        try {
            $report->update([
                'status' => $validated['status'],
                'admin_response' => $validated['admin_response'],
                'assigned_admin_id' => $user->id,
                'verified' => $validated['verified'] ?? $report->verified,
                'resolved_at' => in_array($validated['status'], ['resolved', 'rejected']) ? now() : $report->resolved_at,
            ]);

            // If action_taken is specified, update karenderia status
            if (!empty($validated['action_taken']) && $validated['action_taken'] !== 'none') {
                $this->applyKarenderiaAction($report->karenderia, $validated['action_taken']);
            }

            Log::info("Admin investigated report", [
                'report_id' => $reportId,
                'admin_id' => $user->id,
                'status' => $validated['status'],
                'action' => $validated['action_taken'] ?? 'none',
            ]);

            return response()->json([
                'message' => 'Report updated successfully',
                'data' => $report->refresh()
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error updating report", [
                'report_id' => $reportId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to update report'
            ], 500);
        }
    }

    /**
     * Apply action to karenderia based on report findings
     */
    private function applyKarenderiaAction(Karenderia $karenderia, string $action): void
    {
        try {
            switch ($action) {
                case 'warning':
                    $karenderia->update(['status' => 'active', 'warning_count' => ($karenderia->warning_count ?? 0) + 1]);
                    Log::warning("Karenderia warned", ['karenderia_id' => $karenderia->id]);
                    break;

                case 'suspension':
                    $karenderia->update(['status' => 'suspended']);
                    Log::warning("Karenderia suspended", ['karenderia_id' => $karenderia->id]);
                    break;

                case 'permanent_closure':
                    $karenderia->update(['status' => 'permanently_closed']);
                    Log::warning("Karenderia permanently closed", ['karenderia_id' => $karenderia->id]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Error applying karenderia action", [
                'karenderia_id' => $karenderia->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
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
