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
            <div class="card-body p-0">
                @if($users->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead>
                                <tr style="background-color: #f8d365; color: #333; font-weight: 700;">
                                    <th class="ps-3">User</th>
                                    <th>Role</th>
                                    <th>Verified</th>
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
                                            @elseif($user->application_status === 'approved' || $user->karenderia->status === 'approved')
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
                                        <div class="registered-date">{{ $user->created_at->format('M d, Y') }}</div>
                                        <small class="registered-time">{{ $user->created_at->diffForHumans() }}</small>
                                    </td>
                                    <td class="pe-3">
                                        @if($user->role === 'karenderia_owner')
                                            @php
                                                $karenderia = $user->karenderia;
                                            @endphp
                                            @if($karenderia)
                                                @if($karenderia->status === 'approved')
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
                        {{ $users->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>No Users Found</h5>
                        <p class="text-muted mb-0">No registered users at the moment.</p>
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
</style>
@endsection