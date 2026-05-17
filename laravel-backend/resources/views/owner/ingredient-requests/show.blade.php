@extends('layouts.owner')

@section('title', $ingredientRequest->title . ' - Ingredient Request')
@section('page-title', 'Ingredient Request Detail')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('owner.ingredient-requests') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Requests
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Request Details -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-{{ $ingredientRequest->status === 'open' ? 'warning' : ($ingredientRequest->status === 'accepted' ? 'success' : 'secondary') }}">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="card-title mb-0" style="color: white; font-weight: 700;">
                                {{ $ingredientRequest->title }}
                            </h4>
                        </div>
                        <span class="badge bg-light text-dark fs-6">{{ ucfirst($ingredientRequest->status) }}</span>
                    </div>
                </div>

                <div class="card-body">
                    <!-- Description -->
                    @if($ingredientRequest->description)
                    <div class="mb-4">
                        <h6 style="color: #374151; font-weight: 700;">Description</h6>
                        <p>{{ $ingredientRequest->description }}</p>
                    </div>
                    @endif

                    <!-- Details Grid -->
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block" style="font-weight: 600;">Ingredient Type</small>
                            <p class="mb-0">{{ $ingredientRequest->ingredient_type }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block" style="font-weight: 600;">Quantity Needed</small>
                            <p class="mb-0"><strong>{{ $ingredientRequest->needed_quantity }} {{ $ingredientRequest->unit }}</strong></p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block" style="font-weight: 600;">Needed By Date</small>
                            <p class="mb-0">{{ $ingredientRequest->needed_by_date->format('M d, Y (D)') }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <small class="text-muted d-block" style="font-weight: 600;">Budget</small>
                            <p class="mb-0">{{ $ingredientRequest->budget ? '₱' . number_format($ingredientRequest->budget, 2) : 'No budget set' }}</p>
                        </div>
                        @if($ingredientRequest->delivery_address)
                        <div class="col-12 mb-3">
                            <small class="text-muted d-block" style="font-weight: 600;">Delivery Address</small>
                            <p class="mb-0">{{ $ingredientRequest->delivery_address }}</p>
                        </div>
                        @endif
                        <div class="col-md-6 mb-0">
                            <small class="text-muted d-block" style="font-weight: 600;">Posted</small>
                            <p class="mb-0">{{ $ingredientRequest->created_at->format('M d, Y H:i') }}</p>
                        </div>
                        <div class="col-md-6 mb-0">
                            <small class="text-muted d-block" style="font-weight: 600;">Expires</small>
                            <p class="mb-0">{{ $ingredientRequest->created_at->addHours($ingredientRequest->expiry_hours)->format('M d, Y H:i') }}</p>
                        </div>
                    </div>

                    @if($ingredientRequest->status === 'open')
                    <div class="alert alert-warning">
                        <i class="fas fa-hourglass-end me-2"></i>
                        <strong>Still Open:</strong> Suppliers can continue to submit offers until 
                        {{ $ingredientRequest->created_at->addHours($ingredientRequest->expiry_hours)->diffForHumans() }}
                    </div>
                    @endif
                </div>
            </div>

            <!-- Supplier Quotes -->
            <div class="card">
                <div class="card-header header-success">
                    <h5 class="card-title mb-0" style="color: #1f2937; font-weight: 700;">
                        <i class="fas fa-handshake me-2"></i>Supplier Offers ({{ $ingredientRequest->quotes->count() }})
                    </h5>
                </div>

                <div class="card-body">
                    @forelse($ingredientRequest->quotes as $quote)
                    @php
                        $borderColorClass = $quote->status === 'accepted' ? 'border-success' : ($quote->status === 'pending' ? 'border-warning' : 'border-danger');
                    @endphp
                    <div class="quote-card mb-3 p-3 border rounded-2 {{ $borderColorClass }}" style="border-left-width: 4px;">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Supplier Info -->
                                <div class="mb-2">
                                    <h6 class="mb-0" style="color: #1f2937; font-weight: 700;">
                                        @if($quote->supplier)
                                            {{ $quote->supplier->name }}
                                            @if($quote->status === 'accepted')
                                            <span class="badge bg-success ms-2">Accepted</span>
                                            @endif
                                        @endif
                                    </h6>
                                    @if($quote->supplier && $quote->supplier->phone_number)
                                    <small class="text-muted">
                                        <i class="fas fa-phone me-1"></i>{{ $quote->supplier->phone_number }}
                                    </small>
                                    @endif
                                </div>

                                <!-- Quote Details -->
                                <div class="row mt-2 mb-3">
                                    <div class="col-6">
                                        <small class="text-muted d-block">Price Per Unit</small>
                                        <strong>₱{{ number_format($quote->price_per_unit, 2) }}/{{ $quote->unit }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Available Quantity</small>
                                        <strong>{{ $quote->available_quantity }} {{ $quote->unit }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Total Price</small>
                                        <strong class="text-success">₱{{ number_format($quote->total_price, 2) }}</strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted d-block">Delivery Date</small>
                                        <strong>{{ $quote->delivery_date ? $quote->delivery_date->format('M d, Y') : 'TBD' }}</strong>
                                    </div>
                                </div>

                                <!-- Notes -->
                                @if($quote->notes)
                                <div class="mb-2">
                                    <small class="text-muted d-block">Notes</small>
                                    <p class="mb-0" style="color: #6b7280;">{{ $quote->notes }}</p>
                                </div>
                                @endif

                                <!-- Delivery Method -->
                                @if($quote->delivery_method)
                                <small class="badge bg-light text-dark">
                                    <i class="fas fa-truck me-1"></i>{{ ucfirst($quote->delivery_method) }}
                                </small>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="col-md-4">
                                <div class="d-flex flex-column gap-2">
                                    @if($ingredientRequest->status === 'open' && $quote->status === 'pending')
                                    <form action="{{ route('owner.supplier-quotes.accept', $quote->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success w-100 btn-sm">
                                            <i class="fas fa-check me-1"></i>Accept Offer
                                        </button>
                                    </form>
                                    <form action="{{ route('owner.supplier-quotes.reject', $quote->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-danger w-100 btn-sm">
                                            <i class="fas fa-times me-1"></i>Reject
                                        </button>
                                    </form>
                                    @endif

                                    <a href="#messageModal" 
                                       class="btn btn-outline-primary btn-sm"
                                       data-bs-toggle="modal"
                                       data-supplier-id="{{ $quote->supplier->id }}"
                                       data-supplier-name="{{ $quote->supplier->name }}">
                                        <i class="fas fa-comment me-1"></i>Message
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-4">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <p class="text-muted">No supplier offers yet</p>
                        @if($ingredientRequest->status === 'open')
                        <small class="text-muted d-block">
                            Suppliers will start submitting offers once they see your request
                        </small>
                        @endif
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar: Status & Actions -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Status</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-{{ $ingredientRequest->status === 'open' ? 'warning' : ($ingredientRequest->status === 'accepted' ? 'success' : 'secondary') }} mb-3">
                        <i class="fas fa-circle me-2"></i>
                        <strong>{{ ucfirst($ingredientRequest->status) }}</strong>
                    </div>

                    @if($ingredientRequest->status === 'accepted' && $ingredientRequest->acceptedSupplier)
                    <div class="alert alert-info">
                        <strong>Accepted by:</strong><br>
                        {{ $ingredientRequest->acceptedSupplier->name }}
                    </div>
                    @endif
                </div>
            </div>

            @if($ingredientRequest->status === 'open')
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('owner.ingredient-requests.update-status', $ingredientRequest->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="cancelled">
                        <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Cancel this request?')">
                            <i class="fas fa-ban me-2"></i>Cancel Request
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
.quote-card {
    background-color: #f9fafb;
    transition: all 0.3s ease;
}

.quote-card:hover {
    background-color: #f3f4f6;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

.quote-card.border-success {
    border-left-color: #10b981 !important;
}

.quote-card.border-warning {
    border-left-color: #f59e0b !important;
}

.quote-card.border-danger {
    border-left-color: #ef4444 !important;
}
</style>
@endsection
