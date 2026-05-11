@extends('layouts.admin')

@section('title', 'Review Application - ' . $karenderia->name)
@section('page-title', 'Review Karenderia Application')

@section('content')
<div class="row">
    <!-- Application Details Section -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header header-info">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0" style="color: #1f2937; font-weight: 700;">
                        <i class="fas fa-file-alt me-2"></i>Application Details
                    </h5>
                    <span class="badge bg-{{ $karenderia->status === 'pending' ? 'warning' : ($karenderia->status === 'approved' ? 'success' : 'danger') }}">
                        {{ ucfirst($karenderia->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- Karenderia Information -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-uppercase fs-7 mb-3" style="color: #374151; font-weight: 700;">
                            <i class="fas fa-store me-2"></i>Business Information
                        </h6>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Business Name</label>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->business_name ?? $karenderia->name }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Karenderia Name</label>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->name }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Description</label>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->description ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Operating Days</label>
                            <p class="fw-600" style="color: #111827;">
                                @if($karenderia->operating_days && is_array($karenderia->operating_days))
                                    {{ implode(', ', array_map('ucfirst', $karenderia->operating_days)) }}
                                @else
                                    N/A
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-uppercase fs-7 mb-3" style="color: #374151; font-weight: 700;">
                            <i class="fas fa-clock me-2"></i>Business Hours
                        </h6>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Opening Time</label>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->opening_time ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Closing Time</label>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->closing_time ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Delivery Fee</label>
                            <p class="fw-600" style="color: #111827;">₱{{ $karenderia->delivery_fee ?? '0' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Delivery Time</label>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->delivery_time_minutes ?? 'N/A' }} minutes</p>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Location Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-uppercase fs-7 mb-3" style="color: #374151; font-weight: 700;">
                            <i class="fas fa-map-marker-alt me-2"></i>Location Information
                        </h6>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Address</label>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->address ?? 'N/A' }}</p>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="small" style="color: #6b7280; font-weight: 600;">City</label>
                                <p class="fw-600" style="color: #111827;">{{ $karenderia->city ?? 'N/A' }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="small" style="color: #6b7280; font-weight: 600;">Province</label>
                                <p class="fw-600" style="color: #111827;">{{ $karenderia->province ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Contact Information -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h6 class="text-uppercase fs-7 mb-3" style="color: #374151; font-weight: 700;">
                            <i class="fas fa-phone me-2"></i>Contact Information
                        </h6>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Business Email</label>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->business_email ?? 'N/A' }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="small" style="color: #6b7280; font-weight: 600;">Phone Number</label>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->phone ?? 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Business Permit Section -->
        <div class="card mb-4">
            <div class="card-header header-gold">
                <h5 class="card-title mb-0" style="color: #1f2937; font-weight: 700;">
                    <i class="fas fa-file-pdf me-2"></i>Business Permit
                </h5>
            </div>
            <div class="card-body">
                @if($businessPermitUrl)
                    <div class="text-center">
                        @if(preg_match('/\.(pdf)$/i', $businessPermitUrl))
                            <div class="mb-3">
                                <a href="{{ $businessPermitUrl }}" target="_blank" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-file-pdf me-2"></i>View PDF
                                </a>
                            </div>
                            <p class="text-muted small">Click the button above to view or download the PDF file</p>
                        @else
                            <div class="image-preview-container mb-3" style="max-height: 500px; overflow: auto; border: 2px solid #ddd; border-radius: 4px; background-color: #f5f5f5; min-height: 200px; display: flex; align-items: center; justify-content: center;">
                                <img src="{{ $businessPermitUrl }}" alt="Business Permit" class="img-fluid" id="permitImage">
                            </div>
                            <a href="{{ $businessPermitUrl }}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-external-link-alt me-2"></i>View Full Size
                            </a>
                        @endif
                    </div>
                @else
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        No business permit uploaded
                    </div>
                @endif
            </div>
        </div>

        <!-- Owner Information -->
        <div class="card">
            <div class="card-header header-primary">
                <h5 class="card-title mb-0" style="color: #1f2937; font-weight: 700;">
                    <i class="fas fa-user me-2"></i>Owner Information
                </h5>
            </div>
            <div class="card-body">
                @if($karenderia->owner)
                    <div class="mb-3">
                        <label class="small" style="color: #6b7280; font-weight: 600;">Owner Name</label>
                        <p class="fw-600" style="color: #111827;">{{ $karenderia->owner->name }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="small" style="color: #6b7280; font-weight: 600;">Email</label>
                        <p class="fw-600" style="color: #111827;">{{ $karenderia->owner->email }}</p>
                    </div>
                    <div class="mb-0">
                        <label class="small" style="color: #6b7280; font-weight: 600;">Member Since</label>
                        <p class="fw-600" style="color: #111827;">{{ $karenderia->owner->created_at->format('M d, Y') }}</p>
                    </div>
                @else
                    <p class="text-muted">Owner information not available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Action Panel -->
    <div class="col-lg-4">
        <!-- Application Timeline -->
        <div class="card mb-4">
            <div class="card-header header-success">
                <h5 class="card-title mb-0" style="color: #1f2937; font-weight: 700;">
                    <i class="fas fa-history me-2"></i>Application Timeline
                </h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <p class="small mb-1" style="color: #6b7280; font-weight: 600;">Submitted</p>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->created_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    @if($karenderia->approved_at)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <p class="small mb-1" style="color: #6b7280; font-weight: 600;">Approved</p>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->approved_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                    @endif

                    @if($karenderia->rejected_at)
                    <div class="timeline-item">
                        <div class="timeline-marker bg-danger"></div>
                        <div class="timeline-content">
                            <p class="small mb-1" style="color: #6b7280; font-weight: 600;">Rejected</p>
                            <p class="fw-600" style="color: #111827;">{{ $karenderia->rejected_at->format('M d, Y H:i') }}</p>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Approval/Rejection Reasons -->
        @if($karenderia->rejection_reason)
        <div class="card mb-4 border-danger">
            <div class="card-header bg-danger bg-opacity-10">
                <h6 class="card-title mb-0 text-danger" style="font-weight: 700;">
                    <i class="fas fa-ban me-2"></i>Rejection Reason
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $karenderia->rejection_reason }}</p>
            </div>
        </div>
        @endif

        @if($karenderia->admin_notes)
        <div class="card mb-4 border-info">
            <div class="card-header bg-info bg-opacity-10">
                <h6 class="card-title mb-0 text-info" style="font-weight: 700;">
                    <i class="fas fa-sticky-note me-2"></i>Admin Notes
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-0">{{ $karenderia->admin_notes }}</p>
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        @if($karenderia->status === 'pending')
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0" style="color: #1f2937; font-weight: 700;">
                    <i class="fas fa-check-circle me-2"></i>Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2 mb-3">
                    <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#approveModal">
                        <i class="fas fa-check me-2"></i>Approve
                    </button>
                </div>
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="fas fa-times me-2"></i>Reject
                    </button>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div class="modal fade" id="approveModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header modal-success">
                        <h5 class="modal-title">
                            <i class="fas fa-check-circle me-2"></i>Approve Application
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('admin.pending.approve-with-notes', $karenderia->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p>Are you sure you want to approve <strong>{{ $karenderia->business_name ?? $karenderia->name }}</strong>?</p>
                            <div class="mb-3">
                                <label for="approveNotes" class="form-label">Admin Notes (Optional)</label>
                                <textarea class="form-control" id="approveNotes" name="admin_notes" rows="3" placeholder="Add any notes about this approval..."></textarea>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Once approved, the owner will be able to log in and access their karenderia dashboard.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check me-2"></i>Approve
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Reject Modal -->
        <div class="modal fade" id="rejectModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header modal-danger">
                        <h5 class="modal-title">
                            <i class="fas fa-times-circle me-2"></i>Reject Application
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('admin.pending.reject-with-notes', $karenderia->id) }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <p>Are you sure you want to reject <strong>{{ $karenderia->business_name ?? $karenderia->name }}</strong>?</p>
                            <div class="mb-3">
                                <label for="rejectionReason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="rejectionReason" name="rejection_reason" rows="3" placeholder="Please provide a detailed reason for rejection..." required></textarea>
                                <small class="text-muted">This reason will be sent to the applicant</small>
                            </div>
                            <div class="mb-3">
                                <label for="rejectNotes" class="form-label">Internal Admin Notes (Optional)</label>
                                <textarea class="form-control" id="rejectNotes" name="admin_notes" rows="2" placeholder="Add internal notes..."></textarea>
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                The applicant will be notified of this rejection with the provided reason.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-times me-2"></i>Reject
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @else
        <div class="card">
            <div class="card-body text-center py-4">
                <p class="text-muted mb-2">This application has already been</p>
                <p class="fs-5 fw-600">
                    <span class="badge bg-{{ $karenderia->status === 'approved' ? 'success' : 'danger' }}">
                        {{ ucfirst($karenderia->status) }}
                    </span>
                </p>
            </div>
        </div>
        @endif

        <!-- Back Button -->
        <div class="mt-3">
            <a href="{{ route('admin.pending') }}" class="btn btn-outline-secondary w-100 mb-2">
                <i class="fas fa-arrow-left me-2"></i>Back to Pending
            </a>
            @if($karenderia->status === 'pending')
            <a href="{{ route('admin.edit-karenderia', $karenderia->id) }}" class="btn btn-outline-warning w-100">
                <i class="fas fa-edit me-2"></i>Edit Application
            </a>
            @endif
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 0;
}

.timeline-item {
    display: flex;
    margin-bottom: 30px;
    position: relative;
}

.timeline-marker {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    margin-right: 15px;
    margin-top: 5px;
    flex-shrink: 0;
}

.timeline-content {
    flex: 1;
}

.image-preview-container {
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.fw-600 {
    font-weight: 600;
}

.fw-500 {
    font-weight: 500;
}

/* Modal Header Styling */
.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.modal-header.modal-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}

.modal-header.modal-danger {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.modal-header .modal-title {
    color: white;
    font-weight: 700;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var permitImage = document.getElementById('permitImage');
    if (permitImage) {
        permitImage.onerror = function() {
            this.style.display = 'none';
            var container = this.parentElement;
            container.innerHTML = '<div class="text-center text-muted"><i class="fas fa-exclamation-circle fa-3x mb-3"></i><p>Unable to load permit image</p></div>';
        };
    }
});
</script>
@endsection
