<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Karenderia;
use App\Models\User;
use App\Models\KarenderiaReview;
use App\Mail\RejectNotification;
use App\Notifications\SupplierApprovedNotification;
use App\Mail\KarenderiaApprovedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PendingController extends Controller
{
    public function index()
    {
        $pendingKarenderias = Karenderia::with('owner')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $pendingSuppliers = User::where('role', 'supplier')
            ->where(function ($query) {
                $query->where('application_status', 'pending')
                      ->orWhereNull('application_status');
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $pendingCount = $pendingKarenderias->total() + $pendingSuppliers->count();

        $stats = [
            'pending_reviews' => KarenderiaReview::where('status', 'pending')->count(),
        ];

        return view('admin.pending', compact('pendingKarenderias', 'pendingSuppliers', 'stats'))->with('pendingCount', $pendingCount);
    }

    public function approve(Request $request, $id)
    {
        try {
            $karenderia = Karenderia::with('owner')->findOrFail($id);
            $karenderia->status = 'approved';
            $karenderia->approved_at = now();
            $karenderia->save();

            if ($karenderia->owner) {
                $karenderia->owner->application_status = 'approved';
                $karenderia->owner->verified = true;
                $karenderia->owner->save();

                // Send approval notification email
                Mail::to($karenderia->owner->email)->send(
                    new KarenderiaApprovedNotification($karenderia)
                );
            }

            return redirect()->route('admin.pending')
                ->with('success', "Karenderia '{$karenderia->name}' has been approved successfully! Approval email sent to owner.");
        } catch (\Exception $e) {
            Log::error('Failed to approve karenderia: ' . $e->getMessage(), [
                'karenderia_id' => $id,
                'exception' => $e
            ]);
            return redirect()->route('admin.pending')
                ->with('error', 'Failed to approve karenderia. Please try again.');
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);
        try {
            $karenderia = Karenderia::with('owner')->findOrFail($id);
            $karenderia->status = 'rejected';
            $karenderia->rejection_reason = $request->rejection_reason;
            $karenderia->rejected_at = now();
            $karenderia->save();

            if ($karenderia->owner) {
                $karenderia->owner->application_status = 'rejected';
                $karenderia->owner->verified = false;
                $karenderia->owner->save();

                // Send rejection notification email
                Mail::to($karenderia->owner->email)->send(
                    new RejectNotification($karenderia, $request->rejection_reason)
                );
            }

            return redirect()->route('admin.pending')
                ->with('success', "Karenderia '{$karenderia->name}' has been rejected. Notification email sent to owner.");
        } catch (\Exception $e) {
            Log::error('Failed to reject karenderia: ' . $e->getMessage(), [
                'karenderia_id' => $id,
                'exception' => $e
            ]);
            return redirect()->route('admin.pending')
                ->with('error', 'Failed to reject karenderia. Please try again.');
        }
    }

    public function approveUser(Request $request, $id)
    {
        try {
            $user = \App\Models\User::findOrFail($id);
            $user->application_status = 'approved';
            $user->verified = true;
            $user->save();

            // Send approval notification email
            if ($user->role === 'supplier') {
                $user->notify(new SupplierApprovedNotification('supplier'));
            }

            return redirect()->route('admin.pending')
                ->with('success', "User '{$user->name}' has been approved successfully!");
        } catch (\Exception $e) {
            return redirect()->route('admin.pending')
                ->with('error', 'Failed to approve user. Please try again.');
        }
    }

    public function rejectUser(Request $request, $id)
    {
        try {
            $user = \App\Models\User::findOrFail($id);
            $user->application_status = 'rejected';
            $user->verified = false;
            $user->save();

            return redirect()->route('admin.pending')
                ->with('success', "User '{$user->name}' has been rejected.");
        } catch (\Exception $e) {
            return redirect()->route('admin.pending')
                ->with('error', 'Failed to reject user. Please try again.');
        }
    }
    public function businessPermit(Request $request, $id)
    {
        $karenderia = Karenderia::findOrFail($id);
        $permitPath = storage_path('app/public/' . $karenderia->business_permit);

        if (!$karenderia->business_permit || !file_exists($permitPath)) {
            abort(404, 'Business permit file not found.');
        }

        $download = filter_var($request->query('download', false), FILTER_VALIDATE_BOOLEAN);

        if ($download) {
            return response()->download($permitPath, basename($permitPath));
        }

        return response()->file($permitPath);
    }

    /**
     * Show detailed review page for a karenderia application
     */
    public function review($id)
    {
        $karenderia = Karenderia::with('owner')->findOrFail($id);
        $businessPermitUrl = null;

        if ($karenderia->business_permit) {
            $filePath = 'storage/' . $karenderia->business_permit;
            $fullPath = public_path($filePath);
            
            // Only set URL if file actually exists
            if (file_exists($fullPath)) {
                $businessPermitUrl = asset($filePath);
            }
        }

        return view('admin.review-application', compact('karenderia', 'businessPermitUrl'));
    }

    /**
     * Approve karenderia with optional admin notes
     */
    public function approveWithNotes(Request $request, $id)
    {
        $request->validate([
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $karenderia = Karenderia::findOrFail($id);
            $karenderia->status = 'approved';
            $karenderia->approved_at = now();
            $karenderia->approved_by = Auth::id();
            $karenderia->admin_notes = $request->admin_notes;
            $karenderia->save();

            if ($karenderia->owner) {
                $karenderia->owner->application_status = 'approved';
                $karenderia->owner->verified = true;
                $karenderia->owner->save();
            }

            return redirect()->route('admin.pending')
                ->with('success', "Karenderia '{$karenderia->name}' has been approved successfully!");
        } catch (\Exception $e) {
            return redirect()->route('admin.review-application', $id)
                ->with('error', 'Failed to approve karenderia. Please try again.');
        }
    }

    /**
     * Reject karenderia with required rejection reason and optional notes
     */
    public function rejectWithNotes(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:500',
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        try {
            $karenderia = Karenderia::with('owner')->findOrFail($id);
            $karenderia->status = 'rejected';
            $karenderia->rejection_reason = $request->rejection_reason;
            $karenderia->rejected_at = now();
            $karenderia->admin_notes = $request->admin_notes;
            $karenderia->save();

            if ($karenderia->owner) {
                $karenderia->owner->application_status = 'rejected';
                $karenderia->owner->verified = false;
                $karenderia->owner->save();
            }

            // Send rejection notification email
            if ($karenderia->owner) {
                Mail::to($karenderia->owner->email)->send(
                    new RejectNotification($karenderia, $request->rejection_reason)
                );
            }

            return redirect()->route('admin.pending')
                ->with('success', "Karenderia '{$karenderia->name}' has been rejected. Notification email sent to owner.");
        } catch (\Exception $e) {
            Log::error('Failed to reject karenderia: ' . $e->getMessage(), [
                'karenderia_id' => $id,
                'exception' => $e
            ]);
            return redirect()->route('admin.review-application', $id)
                ->with('error', 'Failed to reject karenderia. Please try again.');
        }
    }

    public function showPendingDashboard()
    {
        $pendingKarenderias = Karenderia::select('id', 'name', 'description')
            ->where('status', 'pending')
            ->get()
            ->toArray();

        return view('pending.pendingDashboard', ['pendingKarenderias' => $pendingKarenderias]);
    }

    /**
     * Show pending customer reviews for moderation
     */
    public function reviews()
    {
        $pendingReviews = \App\Models\KarenderiaReview::with(['karenderia', 'reviewer'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $reviewStats = [
            'pending_count' => \App\Models\KarenderiaReview::where('status', 'pending')->count(),
            'approved_count' => \App\Models\KarenderiaReview::where('status', 'approved')->count(),
            'rejected_count' => \App\Models\KarenderiaReview::where('status', 'rejected')->count(),
            'total_reviews' => \App\Models\KarenderiaReview::count()
        ];

        $stats = [
            'pending_reviews' => $reviewStats['pending_count'],
        ];

        return view('admin.customer-reviews', compact('pendingReviews', 'reviewStats', 'stats'));
    }

    /**
     * Approve a customer review
     */
    public function approveReview(Request $request, $id)
    {
        try {
            $review = \App\Models\KarenderiaReview::findOrFail($id);
            $review->status = 'approved';
            $review->save();

            return redirect()->route('admin.reviews')
                ->with('success', "Review from {$review->reviewer->name} has been approved.");
        } catch (\Exception $e) {
            Log::error('Failed to approve review: ' . $e->getMessage(), [
                'review_id' => $id,
                'exception' => $e
            ]);
            return redirect()->route('admin.reviews')
                ->with('error', 'Failed to approve review. Please try again.');
        }
    }

    /**
     * Reject a customer review
     */
    public function rejectReview(Request $request, $id)
    {
        $request->validate([
            'moderation_note' => 'nullable|string|max:500'
        ]);

        try {
            $review = \App\Models\KarenderiaReview::findOrFail($id);
            $review->status = 'rejected';
            $review->moderation_note = $request->moderation_note;
            $review->save();

            return redirect()->route('admin.reviews')
                ->with('success', "Review from {$review->reviewer->name} has been rejected.");
        } catch (\Exception $e) {
            Log::error('Failed to reject review: ' . $e->getMessage(), [
                'review_id' => $id,
                'exception' => $e
            ]);
            return redirect()->route('admin.reviews')
                ->with('error', 'Failed to reject review. Please try again.');
        }
    }
}
