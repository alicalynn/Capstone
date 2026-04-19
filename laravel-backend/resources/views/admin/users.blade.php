@extends('layouts.admin')

@section('title', 'Users Management')
@section('page-title', 'Users Management')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header py-3">
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
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">User</th>
                                    <th>Role</th>
                                    <th>Verified</th>
                                    <th>Registered</th>
                                    <th class="pe-3">Status</th>
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
                                                <div class="fw-semibold">{{ $user->name }}</div>
                                                <small class="text-muted">{{ $user->email }}</small>
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
                                        <div>{{ $user->created_at->format('M d, Y') }}</div>
                                        <small class="text-muted">{{ $user->created_at->diffForHumans() }}</small>
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
    padding: 0.5rem;
    vertical-align: middle;
}

.table-sm th {
    padding: 0.5rem;
    font-weight: 600;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
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
@endsection