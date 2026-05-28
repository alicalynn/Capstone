@extends('layouts.admin')

@section('title', 'Customer Reviews Moderation')
@section('page-title', 'Customer Reviews')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Pending Reviews</h6>
                                <h3 class="text-warning">{{ $reviewStats['pending_count'] }}</h3>
                            </div>
                            <i class="fas fa-hourglass-half fa-3x text-warning opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Approved</h6>
                                <h3 class="text-success">{{ $reviewStats['approved_count'] }}</h3>
                            </div>
                            <i class="fas fa-check-circle fa-3x text-success opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Rejected</h6>
                                <h3 class="text-danger">{{ $reviewStats['rejected_count'] }}</h3>
                            </div>
                            <i class="fas fa-times-circle fa-3x text-danger opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Total Reviews</h6>
                                <h3 class="text-info">{{ $reviewStats['total_reviews'] }}</h3>
                            </div>
                            <i class="fas fa-star fa-3x text-info opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Reviews Table -->
        <div class="card">
            <div class="card-header header-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-star me-2"></i>Pending Customer Reviews
                    </h5>
                    <span class="badge bg-warning fs-6">{{ $reviewStats['pending_count'] }} pending</span>
                </div>
            </div>
            <div class="card-body">
                @if($pendingReviews->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Karenderia</th>
                                    <th>Reviewer</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingReviews as $review)
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="text-primary">{{ $review->karenderia->business_name ?? $review->karenderia->name }}</strong>
                                            <br><small class="text-muted">ID: {{ $review->karenderia_id }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $review->reviewer->name ?? 'Anonymous' }}</strong>
                                            <br><small class="text-muted">{{ $review->reviewer->email ?? 'No email' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star {{ $i <= $review->rating ? 'text-warning' : 'text-muted' }}"></i>
                                            @endfor
                                        </div>
                                        <small class="text-muted">{{ $review->rating }}/5 Rating</small>
                                    </td>
                                    <td>
                                        <div style="max-width: 300px;">
                                            <p class="mb-0 text-truncate" title="{{ $review->comment }}">
                                                {{ $review->comment ?? 'No comment provided' }}
                                            </p>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $review->created_at->format('M d, Y') }}</span>
                                        <br><small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical btn-group-sm" role="group">
                                            <!-- Approve Button -->
                                            <button type="button" class="btn btn-outline-success mb-1" 
                                                data-id="{{ $review->id }}"
                                                data-reviewer="{{ e($review->reviewer->name ?? 'Anonymous') }}"
                                                onclick="approveReview(this)">
                                                <i class="fas fa-check me-1"></i>Approve
                                            </button>
                                            
                                            <!-- Reject Button -->
                                            <button type="button" class="btn btn-outline-danger" 
                                                data-id="{{ $review->id }}"
                                                data-reviewer="{{ e($review->reviewer->name ?? 'Anonymous') }}"
                                                onclick="rejectReview(this)">
                                                <i class="fas fa-times me-1"></i>Reject
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center">
                        {{ $pendingReviews->render('pagination::bootstrap-4') }}
                    </div>
                @else
                    <div class="alert alert-success text-center py-5">
                        <i class="fas fa-check-circle fa-2x mb-3"></i>
                        <h5>No Pending Reviews</h5>
                        <p class="text-muted mb-0">All customer reviews have been processed!</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Approve Review Modal -->
<div class="modal fade" id="approveReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Approve Review</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong>approve</strong> this review from <strong id="approve-reviewer-name"></strong>?</p>
                <p class="text-muted">The review will be published on the karenderia's profile.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="approveForm" method="POST" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-success">Yes, Approve</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reject Review Modal -->
<div class="modal fade" id="rejectReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Reject Review</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to <strong>reject</strong> this review from <strong id="reject-reviewer-name"></strong>?</p>
                
                <form id="rejectForm" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="moderation_note" class="form-label">Reason for Rejection (Optional)</label>
                        <textarea class="form-control" id="moderation_note" name="moderation_note" rows="3" placeholder="Brief explanation for rejection..."></textarea>
                        <small class="text-muted">This note is for admin records only and won't be shown to the reviewer.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" onclick="document.getElementById('rejectForm').submit();">Reject Review</button>
            </div>
        </div>
    </div>
</div>

<script>
function approveReview(button) {
    const reviewId = button.getAttribute('data-id');
    const reviewerName = button.getAttribute('data-reviewer');
    
    document.getElementById('approve-reviewer-name').textContent = reviewerName;
    document.getElementById('approveForm').action = `/admin/reviews/${reviewId}/approve`;
    
    const modal = new bootstrap.Modal(document.getElementById('approveReviewModal'));
    modal.show();
}

function rejectReview(button) {
    const reviewId = button.getAttribute('data-id');
    const reviewerName = button.getAttribute('data-reviewer');
    
    document.getElementById('reject-reviewer-name').textContent = reviewerName;
    document.getElementById('rejectForm').action = `/admin/reviews/${reviewId}/reject`;
    
    // Clear previous notes
    document.getElementById('moderation_note').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('rejectReviewModal'));
    modal.show();
}
</script>

<style>
.card-stats {
    border-left: 4px solid #ffc107;
}

.card-stats:hover {
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
</style>
@endsection
