@extends('layouts.admin')

@section('title', 'Reports')
@section('page-title', 'Customer Reports')

@section('content')
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Open Reports</h6>
                        <h3 class="text-warning">{{ $stats['open_reports'] }}</h3>
                        <small class="text-muted">Need investigation</small>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
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

    <div class="col-md-4 mb-3">
        <div class="card stat-card success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title text-muted">Pending Approvals</h6>
                        <h3 class="text-success">{{ $stats['pending_approvals'] }}</h3>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header header-danger d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>Customer Reports
                </h5>
                <span class="badge bg-light text-dark">{{ $reports->total() }} total</span>
            </div>
            <div class="card-body">
                @if($reports->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Karenderia</th>
                                    <th>Reporter</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($reports as $report)
                                    <tr>
                                        <td>
                                            <strong>{{ $report->karenderia->business_name ?? $report->karenderia->name ?? ('Karenderia #' . $report->karenderia_id) }}</strong>
                                        </td>
                                        <td>
                                            <div>{{ $report->reporter->name ?? 'Anonymous' }}</div>
                                            <small class="text-muted">{{ $report->reporter->email ?? '' }}</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary text-uppercase">{{ str_replace('_', ' ', $report->report_type) }}</span>
                                        </td>
                                        <td>
                                            @if($report->status === 'new')
                                                <span class="badge bg-danger">New</span>
                                            @elseif($report->status === 'under_review')
                                                <span class="badge bg-warning text-dark">Under Review</span>
                                            @elseif($report->status === 'acknowledged')
                                                <span class="badge bg-info text-dark">Acknowledged</span>
                                            @elseif($report->status === 'resolved')
                                                <span class="badge bg-success">Resolved</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($report->status) }}</span>
                                            @endif
                                        </td>
                                        <td style="max-width: 340px;">
                                            <div class="text-truncate" title="{{ $report->description }}">{{ $report->description }}</div>
                                            @if($report->similar_reports_count > 0)
                                                <small class="text-muted">Similar reports: {{ $report->similar_reports_count }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $report->created_at->format('M d, Y') }}</div>
                                            <small class="text-muted">{{ $report->created_at->diffForHumans() }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $reports->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-shield-alt fa-3x text-muted mb-3"></i>
                        <p class="text-muted mb-0">No open customer reports right now.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection