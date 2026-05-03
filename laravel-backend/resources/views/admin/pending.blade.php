@extends('layouts.admin')

@section('title', 'Pending Approvals')
@section('page-title', 'Pending Approvals')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock me-2"></i>Pending Applications
                    </h5>
                    <span class="badge bg-warning fs-6">{{ $pendingCount }} pending</span>
                </div>
            </div>
            <div class="card-body">
                @if($pendingKarenderias->count() > 0 || $pendingSuppliers->count() > 0)
                    @if($pendingKarenderias->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Karenderia Details</th>
                                    <th>Owner Information</th>
                                    <th>Contact</th>
                                    <th>Business Permit</th>
                                    <th>Application Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingKarenderias as $karenderia)
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="text-primary">{{ $karenderia->name }}</strong>
                                            @if($karenderia->business_name && $karenderia->business_name != $karenderia->name)
                                                <br><small class="text-muted">Business: {{ $karenderia->business_name }}</small>
                                            @endif
                                            <br><small class="text-muted">{{ Str::limit($karenderia->description, 60) }}</small>
                                            <br><small class="text-muted"><i class="fas fa-map-marker-alt"></i> {{ $karenderia->address }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $karenderia->owner->name ?? 'N/A' }}</strong>
                                            <br><small class="text-muted">{{ $karenderia->owner->email ?? 'N/A' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            @if($karenderia->phone)
                                                <i class="fas fa-phone"></i> {{ $karenderia->phone }}<br>
                                            @endif
                                            @if($karenderia->email)
                                                <i class="fas fa-envelope"></i> {{ $karenderia->email }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($karenderia->business_permit)
                                            <div class="d-grid gap-1">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary"
                                                        data-permit-url="{{ route('admin.pending.permit', $karenderia->id) }}"
                                                        data-business-name="{{ e($karenderia->name) }}"
                                                        onclick="previewPermit(this)">
                                                    <i class="fas fa-eye me-1"></i>Preview
                                                </button>
                                                <a href="{{ route('admin.pending.permit', ['id' => $karenderia->id, 'download' => 1]) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-download me-1"></i>Download
                                                </a>
                                            </div>
                                        @else
                                            <span class="badge bg-danger">Missing Permit</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">{{ $karenderia->created_at->format('M d, Y') }}</span>
                                        <br><small class="text-muted">{{ $karenderia->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical btn-group-sm" role="group">
                                            <!-- Approve Button -->
                                                <button type="button" class="btn btn-outline-success mb-1" 
                                                    data-id="{{ $karenderia->id }}"
                                                    data-name="{{ e($karenderia->name) }}"
                                                    onclick="approveKarenderia(this)">
                                                <i class="fas fa-check me-1"></i>Approve
                                            </button>
                                            
                                            <!-- Reject Button -->
                                                <button type="button" class="btn btn-outline-danger" 
                                                    data-id="{{ $karenderia->id }}"
                                                    data-name="{{ e($karenderia->name) }}"
                                                    onclick="showRejectModal(this)">
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
                    <div class="d-flex justify-content-center mt-4">
                        {{ $pendingKarenderias->links() }}
                    </div>
                    @endif

                    @if($pendingSuppliers->count() > 0)
                        <div class="mt-5">
                            <h5 class="card-title mb-3">
                                <i class="fas fa-truck me-2"></i>Pending Supplier Applications
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Supplier Details</th>
                                            <th>Contact</th>
                                            <th>Business Permit</th>
                                            <th>Application Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($pendingSuppliers as $supplier)
                                        <tr>
                                            <td>
                                                <strong class="text-primary">{{ $supplier->name }}</strong>
                                                <br><small class="text-muted">{{ $supplier->email }}</small>
                                            </td>
                                            <td>
                                                <div>
                                                    @if($supplier->phone_number)
                                                        <i class="fas fa-phone"></i> {{ $supplier->phone_number }}<br>
                                                    @endif
                                                    @if($supplier->address)
                                                        <i class="fas fa-map-marker-alt"></i> {{ Str::limit($supplier->address, 60) }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td>
                                                @if($supplier->business_permit)
                                                    @php
                                                        $supplierPermitUrl = url('/business-permits/' . basename($supplier->business_permit));
                                                    @endphp
                                                    <div class="d-grid gap-1">
                                                        <button type="button"
                                                                class="btn btn-sm btn-outline-primary"
                                                                data-permit-url="{{ $supplierPermitUrl }}"
                                                                data-business-name="{{ e($supplier->name) }}"
                                                                onclick="previewPermit(this)">
                                                            <i class="fas fa-eye me-1"></i>Preview
                                                        </button>
                                                        <a href="{{ $supplierPermitUrl }}?download=1" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-download me-1"></i>Download
                                                        </a>
                                                    </div>
                                                @else
                                                    <span class="badge bg-danger">Missing Permit</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">{{ $supplier->created_at->format('M d, Y') }}</span>
                                                <br><small class="text-muted">{{ $supplier->created_at->diffForHumans() }}</small>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-success mb-1"
                                                                data-id="{{ $supplier->id }}"
                                                                data-name="{{ e($supplier->name) }}"
                                                                onclick="approveUser(this)">
                                                        <i class="fas fa-check me-1"></i>Approve
                                                    </button>
                                                            <button type="button" class="btn btn-outline-danger"
                                                                data-id="{{ $supplier->id }}"
                                                                data-name="{{ e($supplier->name) }}"
                                                                onclick="showRejectUserModal(this)">
                                                        <i class="fas fa-times me-1"></i>Reject
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                        <h4>All Caught Up!</h4>
                        <p class="text-muted">No pending karenderia or supplier applications at the moment.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Permit Preview Modal -->
<div class="modal fade" id="permitPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    Business Permit: <span id="permitBusinessName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="height: 75vh;">
                <iframe id="permitPreviewFrame" src="" width="100%" height="100%" style="border: 0; border-radius: 8px;"></iframe>
            </div>
            <div class="modal-footer">
                <a id="permitOpenNewTab" href="#" target="_blank" rel="noopener" class="btn btn-outline-primary">
                    <i class="fas fa-external-link-alt me-2"></i>Open in New Tab
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Approve Confirmation Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-check-circle text-success me-2"></i>Approve Application
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to approve <strong id="approveName"></strong>?</p>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Once approved, this account will be able to log in and access its approved features.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="approveForm" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Approve
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-times-circle text-danger me-2"></i>Reject Application
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="rejectForm" method="POST">
                @csrf
                <div class="modal-body">
                    <p>Are you sure you want to reject <strong id="rejectName"></strong>?</p>
                    
                    <div class="mb-3">
                        <label for="rejection_reason_select" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <select class="form-select" id="rejection_reason_select" onchange="updateReasonText(this.value)" required>
                            <option value="">-- Select a reason --</option>
                            <option value="Invalid permit">Invalid permit</option>
                            <option value="Suspicious activity">Suspicious activity</option>
                            <option value="Incomplete information">Incomplete information</option>
                            <option value="custom">Custom reason...</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rejection_reason" class="form-label">Details <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" 
                                  rows="3" placeholder="Add custom details or explanation..." required></textarea>
                        <small class="text-muted d-block mt-1">
                            The applicant will receive this message via email along with a link to reapply.
                        </small>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action will notify the applicant about the rejection. They can reapply with updated documents immediately.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times me-2"></i>Reject Application
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
function approveKarenderia(button) {
    const id = button.dataset.id;
    const name = button.dataset.name;
    document.getElementById('approveName').textContent = name;
    document.getElementById('approveForm').action = `/admin/pending/${id}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function showRejectModal(button) {
    const id = button.dataset.id;
    const name = button.dataset.name;
    document.getElementById('rejectName').textContent = name;
    document.getElementById('rejectForm').action = `/admin/pending/${id}/reject`;
    document.getElementById('rejection_reason').value = '';
    document.getElementById('rejection_reason_select').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function updateReasonText(selectedValue) {
    const textarea = document.getElementById('rejection_reason');
    if (selectedValue === 'custom' || selectedValue === '') {
        textarea.value = '';
        textarea.placeholder = 'Add custom details or explanation...';
    } else {
        textarea.value = selectedValue;
    }
}

function approveUser(button) {
    const id = button.dataset.id;
    const name = button.dataset.name;
    document.getElementById('approveName').textContent = name;
    document.getElementById('approveForm').action = `/admin/pending/user/${id}/approve`;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function showRejectUserModal(button) {
    const id = button.dataset.id;
    const name = button.dataset.name;
    document.getElementById('rejectName').textContent = name;
    document.getElementById('rejectForm').action = `/admin/pending/user/${id}/reject`;
    document.getElementById('rejection_reason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

function previewPermit(button) {
    const url = button.dataset.permitUrl;
    const businessName = button.dataset.businessName;

    document.getElementById('permitBusinessName').textContent = businessName;
    document.getElementById('permitPreviewFrame').src = url;
    document.getElementById('permitOpenNewTab').href = url;
    new bootstrap.Modal(document.getElementById('permitPreviewModal')).show();
}

document.getElementById('permitPreviewModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('permitPreviewFrame').src = '';
});
</script>
@endsection