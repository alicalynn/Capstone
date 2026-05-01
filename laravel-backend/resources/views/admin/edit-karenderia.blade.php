@extends('layouts.admin')

@section('title', 'Edit Karenderia - ' . $karenderia->name)
@section('page-title', 'Edit Karenderia Application')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header header-gold">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-store me-2"></i>Edit Karenderia Details
                    </h5>
                    <span class="badge bg-{{ $karenderia->status === 'approved' ? 'success' : ($karenderia->status === 'pending' ? 'warning' : 'danger') }}">
                        {{ ucfirst($karenderia->status) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.update-karenderia', $karenderia->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- Owner Information Section -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase fs-7 mb-3">
                            <i class="fas fa-user me-2"></i>Owner Information
                        </h6>

                        <div class="mb-3">
                            <label for="owner_name" class="form-label">Owner Name</label>
                            <input type="text" class="form-control @error('owner_name') is-invalid @enderror" 
                                   id="owner_name" name="owner_name" 
                                   value="{{ old('owner_name', $karenderia->owner?->name) }}" 
                                   placeholder="Enter owner name">
                            @error('owner_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="owner_email" class="form-label">Owner Email</label>
                            <input type="email" class="form-control @error('owner_email') is-invalid @enderror" 
                                   id="owner_email" name="owner_email" 
                                   value="{{ old('owner_email', $karenderia->owner?->email) }}" 
                                   placeholder="Enter owner email">
                            @error('owner_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="owner_phone" class="form-label">Owner Phone</label>
                            <input type="text" class="form-control @error('owner_phone') is-invalid @enderror" 
                                   id="owner_phone" name="owner_phone" 
                                   value="{{ old('owner_phone', $karenderia->owner?->phone) }}" 
                                   placeholder="Enter owner phone">
                            @error('owner_phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>

                    <!-- Business Information Section -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase fs-7 mb-3">
                            <i class="fas fa-store me-2"></i>Business Information
                        </h6>

                        <div class="mb-3">
                            <label for="name" class="form-label">Karenderia Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $karenderia->name) }}" 
                                   placeholder="Enter karenderia name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="business_name" class="form-label">Business Name</label>
                            <input type="text" class="form-control @error('business_name') is-invalid @enderror" 
                                   id="business_name" name="business_name" 
                                   value="{{ old('business_name', $karenderia->business_name) }}" 
                                   placeholder="Enter business name">
                            @error('business_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3" 
                                      placeholder="Enter business description">{{ old('description', $karenderia->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Business Phone</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone', $karenderia->phone) }}" 
                                   placeholder="Enter business phone">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="business_email" class="form-label">Business Email</label>
                            <input type="email" class="form-control @error('business_email') is-invalid @enderror" 
                                   id="business_email" name="business_email" 
                                   value="{{ old('business_email', $karenderia->business_email) }}" 
                                   placeholder="Enter business email">
                            @error('business_email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>

                    <!-- Location Information Section -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase fs-7 mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>Location Information
                        </h6>

                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <input type="text" class="form-control @error('address') is-invalid @enderror" 
                                   id="address" name="address" value="{{ old('address', $karenderia->address) }}" 
                                   placeholder="Enter street address">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control @error('city') is-invalid @enderror" 
                                           id="city" name="city" value="{{ old('city', $karenderia->city) }}" 
                                           placeholder="Enter city">
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="province" class="form-label">Province</label>
                                    <input type="text" class="form-control @error('province') is-invalid @enderror" 
                                           id="province" name="province" value="{{ old('province', $karenderia->province) }}" 
                                           placeholder="Enter province">
                                    @error('province')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Operating Hours Section -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase fs-7 mb-3">
                            <i class="fas fa-clock me-2"></i>Operating Hours
                        </h6>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="opening_time" class="form-label">Opening Time</label>
                                    <input type="time" class="form-control @error('opening_time') is-invalid @enderror" 
                                           id="opening_time" name="opening_time" 
                                           value="{{ old('opening_time', $karenderia->opening_time) }}">
                                    @error('opening_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="closing_time" class="form-label">Closing Time</label>
                                    <input type="time" class="form-control @error('closing_time') is-invalid @enderror" 
                                           id="closing_time" name="closing_time" 
                                           value="{{ old('closing_time', $karenderia->closing_time) }}">
                                    @error('closing_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Form Actions -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="{{ route('admin.review-application', $karenderia->id) }}" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Info Panel -->
    <div class="col-lg-4">
        <!-- Update Alert -->
        <div class="card mb-4 border-info">
            <div class="card-body">
                <h6 class="text-info mb-2">
                    <i class="fas fa-info-circle me-2"></i>Edit Guidelines
                </h6>
                <ul class="mb-0 small text-muted">
                    <li>Verify all changes are accurate</li>
                    <li>Required fields are marked with *</li>
                    <li>All updates are logged and audited</li>
                    <li>Changes will be immediately saved</li>
                </ul>
            </div>
        </div>

        <!-- Verification Alert -->
        <div class="card mb-4 border-success">
            <div class="card-body">
                <h6 class="text-success mb-2">
                    <i class="fas fa-check-circle me-2"></i>Update Verification
                </h6>
                <p class="mb-0 small">
                    <strong>All changes are logged</strong> with timestamp and admin ID for complete audit trail. You can verify updates were made in the application review page.
                </p>
            </div>
        </div>

        <!-- Current Status -->
        <div class="card mb-4">
            <div class="card-header header-success">
                <h6 class="card-title mb-0">Current Status</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small">Karenderia ID</label>
                    <p class="fw-600">#{{ $karenderia->id }}</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Status</label>
                    <p class="fw-600">
                        <span class="badge bg-{{ $karenderia->status === 'approved' ? 'success' : ($karenderia->status === 'pending' ? 'warning' : 'danger') }}">
                            {{ ucfirst($karenderia->status) }}
                        </span>
                    </p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Created</label>
                    <p class="fw-600">{{ $karenderia->created_at->format('M d, Y H:i') }}</p>
                </div>
                <div class="mb-0">
                    <label class="text-muted small">Last Modified</label>
                    <p class="fw-600">{{ $karenderia->updated_at->format('M d, Y H:i') }}</p>
                </div>
            </div>
        </div>

        <!-- Action Links -->
        <div class="card">
            <div class="card-body">
                <a href="{{ route('admin.review-application', $karenderia->id) }}" class="btn btn-outline-primary btn-sm w-100 mb-2">
                    <i class="fas fa-eye me-2"></i>View Details
                </a>
                <a href="{{ route('admin.pending') }}" class="btn btn-outline-secondary btn-sm w-100">
                    <i class="fas fa-arrow-left me-2"></i>Back to Pending
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>
@endsection
