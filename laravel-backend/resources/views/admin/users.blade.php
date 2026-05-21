@extends('layouts.admin')

@section('title', 'Users Management')
@section('page-title', 'Users Management')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header header-gold py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="fas fa-users me-2"></i>All Users
                    </h6>
                    <span class="badge bg-primary">{{ $users->total() }} users</span>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="card-body border-bottom p-3 bg-light">
                <form method="GET" action="{{ route('admin.users') }}" class="row g-3 align-items-end">
                    <!-- Search -->
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Search</label>
                        <input type="text" class="form-control form-control-sm" name="search" 
                               placeholder="Name or email..." value="{{ $filters['search'] ?? '' }}">
                    </div>

                    <!-- Role Filter -->
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Role</label>
                        <select class="form-select form-select-sm" name="role">
                            <option value="">All Roles</option>
                            <option value="customer" {{ ($filters['role'] ?? '') === 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="karenderia_owner" {{ ($filters['role'] ?? '') === 'karenderia_owner' ? 'selected' : '' }}>Karenderia Owner</option>
                            <option value="supplier" {{ ($filters['role'] ?? '') === 'supplier' ? 'selected' : '' }}>Supplier</option>
                            <option value="admin" {{ ($filters['role'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                    </div>

                    <!-- Verified Status -->
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Status</label>
                        <select class="form-select form-select-sm" name="app_status">
                            <option value="">All Status</option>
                            <option value="active" {{ ($filters['app_status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="pending" {{ ($filters['app_status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending Approval</option>
                            <option value="rejected" {{ ($filters['app_status'] ?? '') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>

                    <!-- Verified Filter -->
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">Verified</label>
                        <select class="form-select form-select-sm" name="verified">
                            <option value="">All Users</option>
                            <option value="yes" {{ ($filters['verified'] ?? '') === 'yes' ? 'selected' : '' }}>Verified Only</option>
                            <option value="no" {{ ($filters['verified'] ?? '') === 'no' ? 'selected' : '' }}>Unverified Only</option>
                        </select>
                    </div>

                    <!-- Date Range -->
                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">From Date</label>
                        <input type="date" class="form-control form-control-sm" name="date_from" 
                               value="{{ $filters['date_from'] ?? '' }}">
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-semibold">To Date</label>
                        <input type="date" class="form-control form-control-sm" name="date_to" 
                               value="{{ $filters['date_to'] ?? '' }}">
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a href="{{ route('admin.users') }}" class="btn btn-sm btn-secondary w-100">
                            <i class="fas fa-redo me-1"></i>Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                @if($users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr style="background-color: #f8d365; color: #333; font-weight: 700;">
                                    <th class="ps-3">User</th>
                                    <th>Role</th>
                                    <th>Verified</th>
                                    <th>Business Permit</th>
                                    <th>Registered</th>
                                    <th>Status</th>
                                    <th class="pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold user-name">{{ $user->name }}</div>
                                                <small class="user-email">{{ $user->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($user->role === 'admin')
                                            <span class="badge bg-danger">Admin</span>
                                        @elseif($user->role === 'karenderia_owner')
                                            <span class="badge bg-warning text-dark">Karenderia Owner</span>
                                        @elseif($user->role === 'supplier')
                                            <span class="badge bg-info text-dark">Supplier</span>
                                        @else
                                            <span class="badge bg-primary">Customer</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->role === 'supplier')
                                            @if($user->application_status === 'approved')
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Verified
                                                </span>
                                            @elseif($user->application_status === 'rejected')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times"></i> Rejected
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-clock"></i> Pending Approval
                                                </span>
                                            @endif
                                        @elseif($user->role === 'karenderia_owner')
                                            @if(!$user->karenderia)
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-info-circle"></i> No Application
                                                </span>
                                            @elseif($user->verified || $user->application_status === 'approved' || in_array($user->karenderia->status, ['approved', 'active']))
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Verified
                                                </span>
                                            @elseif($user->application_status === 'rejected' || $user->karenderia->status === 'rejected')
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-times"></i> Rejected
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-clock"></i> Pending Approval
                                                </span>
                                            @endif
                                        @else
                                            @if($user->verified)
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check"></i> Verified
                                                </span>
                                            @else
                                                <span class="badge bg-warning text-dark">
                                                    <i class="fas fa-clock"></i> Pending
                                                </span>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $permitPreviewUrl = null;
                                            $permitDownloadUrl = null;
                                            $permitLabel = $user->name;

                                            if ($user->role === 'supplier' && $user->business_permit) {
                                                $permitPreviewUrl = url('/business-permits/' . basename($user->business_permit));
                                                $permitDownloadUrl = $permitPreviewUrl . '?download=1';
                                            } elseif ($user->role === 'karenderia_owner' && $user->karenderia && $user->karenderia->business_permit) {
                                                $permitPreviewUrl = route('admin.pending.permit', ['id' => $user->karenderia->id]);
                                                $permitDownloadUrl = route('admin.pending.permit', ['id' => $user->karenderia->id, 'download' => 1]);
                                                $permitLabel = $user->karenderia->name ?? $user->name;
                                            }
                                        @endphp

                                        @if($permitPreviewUrl)
                                            <div class="d-grid gap-1">
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary"
                                                        data-permit-url="{{ $permitPreviewUrl }}"
                                                        data-business-name="{{ e($permitLabel) }}"
                                                        onclick="previewPermit(this)">
                                                    <i class="fas fa-eye me-1"></i>View Permit
                                                </button>
                                                <a href="{{ $permitDownloadUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-download me-1"></i>Download
                                                </a>
                                            </div>
                                        @else
                                            <span class="badge bg-danger">No Permit</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="registered-date">{{ $user->created_at->format('M d, Y') }}</div>
                                        <small class="registered-time">{{ $user->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="pe-3">
                                        @if($user->role === 'karenderia_owner')
                                            @php
                                                $karenderia = $user->karenderia;
                                            @endphp
                                            @if($karenderia)
                                                @if(in_array($karenderia->status, ['approved', 'active']))
                                                    <span class="badge bg-success">Active</span>
                                                @elseif($karenderia->status === 'pending')
                                                    <span class="badge bg-warning text-dark">Pending Approval</span>
                                                @else
                                                    <span class="badge bg-danger">Rejected</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">No Karenderia</span>
                                            @endif
                                        @elseif($user->role === 'supplier')
                                            @if($user->application_status === 'approved')
                                                <span class="badge bg-success">Active</span>
                                            @elseif($user->application_status === 'rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending Approval</span>
                                            @endif
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td class="pe-3">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="{{ route('admin.edit-user', $user->id) }}" class="btn btn-outline-primary" title="Edit user">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            @if($user->karenderia)
                                            <a href="{{ route('admin.review-application', $user->karenderia->id) }}" class="btn btn-outline-info" title="View application">
                                                <i class="fas fa-file-alt"></i>
                                            </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center p-3 border-top">
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>No Users Found</h5>
                        <p class="text-muted mb-0">Try adjusting your filters to see more users.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
.avatar-sm {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    flex-shrink: 0;
}

.table-sm td {
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
    color: #1f2937;
    font-weight: 500;
}

.table-sm tbody tr {
    border-bottom: 1px solid #e5e7eb;
    background-color: #ffffff;
}

.table-sm tbody tr:hover {
    background-color: #f3f4f6;
}

.table-sm th {
    padding: 0.75rem 0.5rem;
    font-weight: 700;
    font-size: 0.875rem;
}

.user-name {
    font-weight: 700;
    color: #111827;
    font-size: 0.95rem;
    display: block;
    margin-bottom: 0.25rem;
}

.user-email {
    color: #6b7280;
    font-weight: 500;
    display: block;
}

.registered-date {
    font-weight: 700;
    color: #111827;
    font-size: 0.95rem;
    display: block;
    margin-bottom: 0.25rem;
}

.registered-time {
    color: #6b7280;
    font-weight: 500;
    display: block;
}

.badge {
    font-size: 0.75rem;
    padding: 0.4rem 0.6rem;
    font-weight: 600;
}

.fw-semibold {
    font-weight: 600;
}

.form-control-sm, .form-select-sm {
    font-size: 0.875rem;
}

.bg-light {
    background-color: #f8f9fa !important;
}
</style>

<!-- Permit Preview Modal -->
<div class="modal fade" id="userPermitPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt text-primary me-2"></i>
                    Business Permit: <span id="userPermitBusinessName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="height: 75vh;">
                <object id="userPermitPreviewFrame" data="" width="100%" height="100%" style="border: 0; border-radius: 8px; background: #fff;"></object>
            </div>
            <div class="modal-footer">
                <a id="userPermitOpenNewTab" href="#" target="_blank" rel="noopener" class="btn btn-outline-primary">
                    <i class="fas fa-external-link-alt me-2"></i>Open in New Tab
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function previewPermit(button) {
    const url = button.dataset.permitUrl;
    const businessName = button.dataset.businessName;

    document.getElementById('userPermitBusinessName').textContent = businessName;
    document.getElementById('userPermitPreviewFrame').data = url;
    document.getElementById('userPermitOpenNewTab').href = url;

    const modal = new bootstrap.Modal(document.getElementById('userPermitPreviewModal'));
    modal.show();

    document.getElementById('userPermitPreviewModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('userPermitPreviewFrame').data = '';
    }, { once: true });
}
</script>
@endsection