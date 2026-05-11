@extends('layouts.owner')

@section('title', 'Ingredient Requests')
@section('page-title', 'Ingredient Requests')

@section('content')
<div class="container-fluid">
    <!-- Header with action button -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h3 class="mb-0" style="color: #1f2937; font-weight: 700;">
                <i class="fas fa-box-open me-2"></i>Your Ingredient Requests
            </h3>
            <p class="text-muted mt-2">Post requests for ingredients and compare supplier offers</p>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('owner.ingredient-requests.create') }}" class="btn btn-success btn-lg">
                <i class="fas fa-plus me-2"></i>Post New Request
            </a>
        </div>
    </div>

    <!-- Filter tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link {{ request('status') !== 'accepted' && request('status') !== 'completed' ? 'active' : '' }}" 
               href="{{ route('owner.ingredient-requests') }}">
                <i class="fas fa-circle-notch me-2"></i>Open Requests
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') === 'accepted' ? 'active' : '' }}" 
               href="{{ route('owner.ingredient-requests', ['status' => 'accepted']) }}">
                <i class="fas fa-check-circle me-2"></i>Accepted
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ request('status') === 'completed' ? 'active' : '' }}" 
               href="{{ route('owner.ingredient-requests', ['status' => 'completed']) }}">
                <i class="fas fa-checkmark me-2"></i>Completed
            </a>
        </li>
    </ul>

    <!-- Requests Grid -->
    <div class="row">
        @forelse($requests as $request)
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card h-100 border-{{ $request->status === 'open' ? 'warning' : ($request->status === 'accepted' ? 'success' : 'secondary') }}">
                <div class="card-header bg-{{ $request->status === 'open' ? 'warning' : ($request->status === 'accepted' ? 'success' : 'secondary') }} bg-opacity-10">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="card-title mb-2">{{ $request->title }}</h5>
                            <span class="badge bg-{{ $request->status === 'open' ? 'warning' : ($request->status === 'accepted' ? 'success' : 'secondary') }}">
                                {{ ucfirst($request->status) }}
                            </span>
                        </div>
                        <span class="badge bg-info">{{ $request->quotes_count ?? 0 }} Quotes</span>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted small">{{ $request->description }}</p>
                    
                    <div class="mb-3">
                        <small class="text-muted d-block">Quantity Needed</small>
                        <strong>{{ $request->needed_quantity }} {{ $request->unit }}</strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Type</small>
                        <strong>{{ $request->ingredient_type }}</strong>
                    </div>

                    <div class="mb-3">
                        <small class="text-muted d-block">Needed By</small>
                        <strong>{{ $request->needed_by_date->format('M d, Y') }}</strong>
                    </div>

                    @if($request->budget)
                    <div class="mb-3">
                        <small class="text-muted d-block">Budget</small>
                        <strong>₱{{ number_format($request->budget, 2) }}</strong>
                    </div>
                    @endif

                    @if($request->status === 'open')
                    <div class="alert alert-warning alert-sm mb-0">
                        <i class="fas fa-hourglass-end me-2"></i>
                        <small>Expires {{ $request->created_at->addHours($request->expiry_hours)->diffForHumans() }}</small>
                    </div>
                    @endif
                </div>
                <div class="card-footer bg-white">
                    <a href="{{ route('owner.ingredient-requests.show', $request->id) }}" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-eye me-1"></i>View Details & Quotes
                    </a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                    <h5 class="card-title">No Ingredient Requests Yet</h5>
                    <p class="text-muted mb-3">Start by posting your first ingredient request to see supplier offers</p>
                    <a href="{{ route('owner.ingredient-requests.create') }}" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Post Your First Request
                    </a>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($requests->hasPages())
    <div class="row mt-4">
        <div class="col-12">
            {{ $requests->links() }}
        </div>
    </div>
    @endif
</div>

<style>
.card {
    transition: all 0.3s ease;
}

.card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transform: translateY(-2px);
}

.badge {
    font-size: 0.75rem;
    padding: 0.4rem 0.6rem;
}
</style>
@endsection
