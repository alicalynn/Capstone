@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
<div class="row mb-4">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-3">
        <div class="card stat-card primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Total Users</h6>
                        <h3 class="text-primary">{{ $stats['total_users'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Approved Karenderias</h6>
                        <h3 class="text-success">{{ $stats['approved_karenderias'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Pending Approvals</h6>
                        <h3 class="text-warning">{{ $stats['pending_approvals'] }}</h3>
                        <small class="text-muted">Karenderias + suppliers</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Rejected</h6>
                        <h3 class="text-danger">{{ $stats['rejected_karenderias'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Registrations -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>Recent Karenderia Registrations
                </h5>
            </div>
            <div class="card-body">
                @if($recent_registrations->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Karenderia Name</th>
                                    <th>Owner</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent_registrations as $karenderia)
                                <tr>
                                    <td>
                                        <strong>{{ $karenderia->name }}</strong>
                                        <br><small class="text-muted">{{ Str::limit($karenderia->description, 50) }}</small>
                                    </td>
                                    <td>{{ $karenderia->owner->name ?? 'N/A' }}</td>
                                    <td>
                                        @if($karenderia->status == 'pending')
                                            <span class="badge bg-warning">Pending</span>
                                        @elseif($karenderia->status == 'approved')
                                            <span class="badge bg-success">Approved</span>
                                        @else
                                            <span class="badge bg-danger">Rejected</span>
                                        @endif
                                    </td>
                                    <td>{{ $karenderia->created_at->format('M d, Y') }}</td>
                                    <td>
                                        @if($karenderia->status == 'pending')
                                            <a href="{{ route('admin.pending') }}" class="btn btn-sm btn-outline-primary">
                                                Review
                                            </a>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No recent registrations</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('admin.pending') }}" class="btn btn-warning">
                        <i class="fas fa-clock me-2"></i>Review Pending
                        @if($stats['pending_approvals'] > 0)
                            <span class="badge bg-light text-dark ms-2">{{ $stats['pending_approvals'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('admin.users') }}" class="btn btn-primary">
                        <i class="fas fa-users me-2"></i>Manage Users
                    </a>
                    <a href="{{ route('admin.karenderias') }}" class="btn btn-success">
                        <i class="fas fa-store me-2"></i>View Karenderias
                    </a>
                </div>
            </div>
        </div>

        <!-- User Distribution -->
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-pie me-2"></i>User Distribution
                </h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    @php
                        $customerPercent = $stats['total_users'] > 0 ? ($stats['total_customers'] / $stats['total_users']) * 100 : 0;
                    @endphp
                    <div class="d-flex justify-content-between mb-1">
                        <span>Customers</span>
                        <span>{{ $stats['total_customers'] }}</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-primary distribution-progress" data-width="{{ round($customerPercent, 2) }}" style="width: 0;"></div>
                    </div>
                </div>
                <div class="mb-0">
                    @php
                        $ownerPercent = $stats['total_users'] > 0 ? ($stats['total_karenderia_owners'] / $stats['total_users']) * 100 : 0;
                    @endphp
                    <div class="d-flex justify-content-between mb-1">
                        <span>Karenderia Owners</span>
                        <span>{{ $stats['total_karenderia_owners'] }}</span>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success distribution-progress" data-width="{{ round($ownerPercent, 2) }}" style="width: 0;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.distribution-progress').forEach(function (bar) {
        const width = Number(bar.getAttribute('data-width') || 0);
        bar.style.width = `${Math.max(0, Math.min(100, width))}%`;
    });
});
</script>
@endsection