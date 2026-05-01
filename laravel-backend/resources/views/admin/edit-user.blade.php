@extends('layouts.admin')

@section('title', 'Edit User - ' . $user->name)
@section('page-title', 'Edit User')

@section('content')
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header header-primary">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-user-edit me-2"></i>Edit User Details
                    </h5>
                    <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'karenderia_owner' ? 'warning' : ($user->role === 'supplier' ? 'info' : 'primary')) }}">
                        {{ ucfirst(str_replace('_', ' ', $user->role)) }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.update-user', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- User Information -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase fs-7 mb-3">
                            <i class="fas fa-info-circle me-2"></i>User Information
                        </h6>

                        <div class="mb-3">
                            <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name', $user->name) }}" 
                                   placeholder="Enter full name" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                   id="email" name="email" value="{{ old('email', $user->email) }}" 
                                   placeholder="Enter email address" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="text" class="form-control @error('phone') is-invalid @enderror" 
                                   id="phone" name="phone" value="{{ old('phone', $user->phone) }}" 
                                   placeholder="Enter phone number">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <hr>

                    <!-- User Status & Type -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase fs-7 mb-3">
                            <i class="fas fa-shield-alt me-2"></i>Account Status
                        </h6>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <p class="fw-600 text-muted">{{ ucfirst(str_replace('_', ' ', $user->role)) }}</p>
                                <small class="text-muted">Contact admin to change user role</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Verified Status</label>
                                <p class="fw-600">
                                    @if($user->verified)
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i>Verified</span>
                                    @else
                                        <span class="badge bg-warning"><i class="fas fa-clock me-1"></i>Unverified</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase fs-7 mb-3">
                            <i class="fas fa-calendar me-2"></i>Account Dates
                        </h6>

                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Registered</label>
                                <p class="fw-600">{{ $user->created_at->format('M d, Y H:i') }}</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Updated</label>
                                <p class="fw-600">{{ $user->updated_at->format('M d, Y H:i') }}</p>
                            </div>
                        </div>
                    </div>

                    @if($user->karenderia)
                    <hr>
                    <div class="mb-4">
                        <h6 class="text-muted text-uppercase fs-7 mb-3">
                            <i class="fas fa-store me-2"></i>Associated Karenderia
                        </h6>
                        <p class="fw-600">
                            <a href="{{ route('admin.review-application', $user->karenderia->id) }}" class="link-primary">
                                {{ $user->karenderia->name }}
                            </a>
                        </p>
                        <small class="text-muted">
                            Status: <span class="badge bg-{{ $user->karenderia->status === 'approved' ? 'success' : ($user->karenderia->status === 'pending' ? 'warning' : 'danger') }}">
                                {{ ucfirst($user->karenderia->status) }}
                            </span>
                        </small>
                    </div>
                    @endif

                    <hr>

                    <!-- Form Actions -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="{{ route('admin.users') }}" class="btn btn-outline-secondary">
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
                <div class="d-flex align-items-start">
                    <div class="flex-grow-1">
                        <h6 class="text-info mb-2">
                            <i class="fas fa-info-circle me-2"></i>What You Can Edit
                        </h6>
                        <ul class="mb-0 small text-muted">
                            <li>Full name</li>
                            <li>Email address</li>
                            <li>Phone number</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Verification Alert -->
        <div class="card mb-4 border-success">
            <div class="card-body">
                <h6 class="text-success mb-2">
                    <i class="fas fa-check-circle me-2"></i>Update Verification
                </h6>
                <p class="mb-2 small">
                    <strong>All changes are logged</strong> and tracked for security purposes.
                </p>
                <p class="mb-0 small text-muted">
                    After saving, the user will be notified of any account changes via their registered email.
                </p>
            </div>
        </div>

        <!-- User Stats -->
        <div class="card">
            <div class="card-header header-success">
                <h6 class="card-title mb-0">User Details</h6>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="text-muted small">User ID</label>
                    <p class="fw-600">#{{ $user->id }}</p>
                </div>
                <div class="mb-3">
                    <label class="text-muted small">Current Status</label>
                    <p class="fw-600">
                        @if($user->is_active ?? true)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </p>
                </div>
                <div class="mb-0">
                    <label class="text-muted small">Account Age</label>
                    <p class="fw-600">{{ $user->created_at->diffForHumans() }}</p>
                </div>
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
