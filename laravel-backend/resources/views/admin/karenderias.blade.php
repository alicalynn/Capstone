@extends('layouts.admin')

@section('title', 'Karenderias Management')
@section('page-title', 'Karenderias Management')

@php
use Illuminate\Support\Str;
@endphp

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header header-success">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-store me-2"></i>All Karenderias
                    </h5>
                    <span class="badge bg-light text-dark fs-6">{{ $karenderias->total() }} karenderias</span>
                </div>
            </div>
            <div class="card-body">
                @if($karenderias->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr style="background-color: #11998e; color: white; font-weight: 700;">
                                    <th>Karenderia Details</th>
                                    <th>Owner</th>
                                    <th>Contact Information</th>
                                    <th>Status</th>
                                    <th>Registration Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($karenderias as $karenderia)
                                <tr>
                                    <td>
                                        <div>
                                            <strong class="karenderia-name">{{ $karenderia->name }}</strong>
                                            @if($karenderia->business_name && $karenderia->business_name != $karenderia->name)
                                                <br><small class="karenderia-business">Business: {{ $karenderia->business_name }}</small>
                                            @endif
                                            <br><small class="karenderia-description">{{ Str::limit($karenderia->description, 60) }}</small>
                                            <br><small class="karenderia-address">
                                                <i class="fas fa-map-marker-alt"></i> {{ $karenderia->address }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if($karenderia->owner)
                                            <div>
                                                <strong class="owner-name">{{ $karenderia->owner->name }}</strong>
                                                <br><small class="owner-email">{{ $karenderia->owner->email }}</small>
                                            </div>
                                        @else
                                            <span class="owner-not-found">Owner not found</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="contact-info">
                                            @if($karenderia->phone)
                                                <i class="fas fa-phone"></i> {{ $karenderia->phone }}<br>
                                            @endif
                                            @if($karenderia->email)
                                                <i class="fas fa-envelope"></i> {{ $karenderia->email }}<br>
                                            @endif
                                            @if($karenderia->operating_hours)
                                                <small class="operating-hours">
                                                    <i class="fas fa-clock"></i> 
                                                    {{ $karenderia->opening_time ? $karenderia->opening_time->format('H:i') : 'N/A' }} - 
                                                    {{ $karenderia->closing_time ? $karenderia->closing_time->format('H:i') : 'N/A' }}
                                                </small>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if($karenderia->status === 'approved')
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>Approved
                                            </span>
                                            @if($karenderia->approved_at)
                                                <br><small class="status-date">{{ $karenderia->approved_at->format('M d, Y') }}</small>
                                            @endif
                                        @elseif($karenderia->status === 'pending')
                                            <span class="badge bg-warning">
                                                <i class="fas fa-clock me-1"></i>Pending
                                            </span>
                                        @elseif($karenderia->status === 'rejected')
                                            <span class="badge bg-danger">
                                                <i class="fas fa-times-circle me-1"></i>Rejected
                                            </span>
                                            @if($karenderia->rejected_at)
                                                <br><small class="status-date">{{ $karenderia->rejected_at->format('M d, Y') }}</small>
                                            @endif
                                            @if($karenderia->rejection_reason)
                                                <br><small class="rejection-reason">{{ Str::limit($karenderia->rejection_reason, 30) }}</small>
                                            @endif
                                        @endif
                                    </td>
                                    <td>
                                        <div class="registration-date">{{ $karenderia->created_at->format('M d, Y') }}</div>
                                        <small class="registration-time">{{ $karenderia->created_at->diffForHumans() }}</small>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-4">
                        {{ $karenderias->links() }}
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-store fa-4x text-muted mb-3"></i>
                        <h4>No Karenderias Found</h4>
                        <p class="text-muted">No karenderia registrations at the moment.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
.table.table-hover {
    font-weight: 500;
}

.table.table-hover td {
    color: #1f2937;
    font-weight: 500;
    padding: 0.75rem;
    vertical-align: top;
}

.table.table-hover tbody tr {
    border-bottom: 1px solid #e5e7eb;
    background-color: #ffffff;
}

.table.table-hover tbody tr:hover {
    background-color: #f3f4f6;
}

.table.table-hover th {
    font-weight: 700;
    padding: 0.75rem;
}

.karenderia-name {
    font-weight: 700;
    color: #1f6b59;
    font-size: 0.95rem;
    display: block;
}

.karenderia-business,
.karenderia-description,
.karenderia-address {
    color: #6b7280;
    font-weight: 500;
    display: block;
}

.owner-name {
    font-weight: 700;
    color: #111827;
    font-size: 0.95rem;
    display: block;
}

.owner-email {
    color: #6b7280;
    font-weight: 500;
    display: block;
}

.owner-not-found {
    color: #6b7280;
    font-weight: 500;
}

.contact-info {
    color: #1f2937;
    font-weight: 500;
}

.operating-hours {
    color: #6b7280;
    font-weight: 500;
    display: block;
}

.status-date {
    color: #6b7280;
    font-weight: 500;
    display: block;
}

.rejection-reason {
    color: #dc2626;
    font-weight: 600;
    display: block;
}

.registration-date {
    font-weight: 700;
    color: #111827;
    font-size: 0.95rem;
    display: block;
    margin-bottom: 0.25rem;
}

.registration-time {
    color: #6b7280;
    font-weight: 500;
    display: block;
}
</style>
@endsection