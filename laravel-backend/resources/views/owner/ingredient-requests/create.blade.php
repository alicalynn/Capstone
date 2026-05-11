@extends('layouts.owner')

@section('title', 'Post Ingredient Request')
@section('page-title', 'Post New Ingredient Request')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header header-primary">
                    <h5 class="card-title mb-0" style="color: #1f2937; font-weight: 700;">
                        <i class="fas fa-plus-circle me-2"></i>Post Ingredient Request
                    </h5>
                </div>

                <form action="{{ route('owner.ingredient-requests.store') }}" method="POST" id="ingredientRequestForm">
                    @csrf
                    <div class="card-body">
                        <!-- Request Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label fw-600">Ingredient Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control form-control-lg @error('title') is-invalid @enderror" 
                                   id="title" 
                                   name="title" 
                                   placeholder="e.g., Chicken Breast"
                                   value="{{ old('title') }}"
                                   required>
                            @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="form-label fw-600">Description (Optional)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3"
                                      placeholder="Add any specific requirements (quality, grade, etc.)">{{ old('description') }}</textarea>
                            @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <!-- Ingredient Type -->
                            <div class="col-md-6 mb-4">
                                <label for="ingredient_type" class="form-label fw-600">Type <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg @error('ingredient_type') is-invalid @enderror" 
                                        id="ingredient_type" 
                                        name="ingredient_type"
                                        required>
                                    <option value="">Select Type</option>
                                    <option value="Meat" {{ old('ingredient_type') === 'Meat' ? 'selected' : '' }}>Meat</option>
                                    <option value="Produce" {{ old('ingredient_type') === 'Produce' ? 'selected' : '' }}>Produce</option>
                                    <option value="Dairy" {{ old('ingredient_type') === 'Dairy' ? 'selected' : '' }}>Dairy</option>
                                    <option value="Grains" {{ old('ingredient_type') === 'Grains' ? 'selected' : '' }}>Grains</option>
                                    <option value="Spices" {{ old('ingredient_type') === 'Spices' ? 'selected' : '' }}>Spices</option>
                                    <option value="Condiments" {{ old('ingredient_type') === 'Condiments' ? 'selected' : '' }}>Condiments</option>
                                    <option value="Beverages" {{ old('ingredient_type') === 'Beverages' ? 'selected' : '' }}>Beverages</option>
                                    <option value="Other" {{ old('ingredient_type') === 'Other' ? 'selected' : '' }}>Other</option>
                                </select>
                                @error('ingredient_type')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Quantity -->
                            <div class="col-md-6 mb-4">
                                <label for="needed_quantity" class="form-label fw-600">Quantity <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <input type="number" 
                                           class="form-control @error('needed_quantity') is-invalid @enderror" 
                                           id="needed_quantity" 
                                           name="needed_quantity" 
                                           step="0.01"
                                           placeholder="0.00"
                                           value="{{ old('needed_quantity') }}"
                                           required>
                                    <select class="form-select @error('unit') is-invalid @enderror" 
                                            id="unit" 
                                            name="unit"
                                            required>
                                        <option value="">Unit</option>
                                        <option value="kg" {{ old('unit') === 'kg' ? 'selected' : '' }}>kg</option>
                                        <option value="lbs" {{ old('unit') === 'lbs' ? 'selected' : '' }}>lbs</option>
                                        <option value="pieces" {{ old('unit') === 'pieces' ? 'selected' : '' }}>pieces</option>
                                        <option value="liters" {{ old('unit') === 'liters' ? 'selected' : '' }}>liters</option>
                                        <option value="gallons" {{ old('unit') === 'gallons' ? 'selected' : '' }}>gallons</option>
                                        <option value="boxes" {{ old('unit') === 'boxes' ? 'selected' : '' }}>boxes</option>
                                        <option value="bags" {{ old('unit') === 'bags' ? 'selected' : '' }}>bags</option>
                                    </select>
                                </div>
                                @error('needed_quantity')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row">
                            <!-- Needed By Date -->
                            <div class="col-md-6 mb-4">
                                <label for="needed_by_date" class="form-label fw-600">Needed By <span class="text-danger">*</span></label>
                                <input type="date" 
                                       class="form-control form-control-lg @error('needed_by_date') is-invalid @enderror" 
                                       id="needed_by_date" 
                                       name="needed_by_date"
                                       value="{{ old('needed_by_date') }}"
                                       required>
                                @error('needed_by_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Budget -->
                            <div class="col-md-6 mb-4">
                                <label for="budget" class="form-label fw-600">Budget (Optional)</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" 
                                           class="form-control @error('budget') is-invalid @enderror" 
                                           id="budget" 
                                           name="budget" 
                                           step="0.01"
                                           placeholder="0.00"
                                           value="{{ old('budget') }}">
                                </div>
                                @error('budget')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Delivery Address -->
                        <div class="mb-4">
                            <label for="delivery_address" class="form-label fw-600">Delivery Address (Optional)</label>
                            <textarea class="form-control @error('delivery_address') is-invalid @enderror" 
                                      id="delivery_address" 
                                      name="delivery_address" 
                                      rows="2"
                                      placeholder="Where should suppliers deliver to?">{{ old('delivery_address') }}</textarea>
                            @error('delivery_address')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Request Duration -->
                        <div class="mb-4">
                            <label for="expiry_hours" class="form-label fw-600">How long should this request stay open?</label>
                            <select class="form-select form-select-lg @error('expiry_hours') is-invalid @enderror" 
                                    id="expiry_hours" 
                                    name="expiry_hours">
                                <option value="24" {{ old('expiry_hours', 48) === '24' ? 'selected' : '' }}>24 hours</option>
                                <option value="48" {{ old('expiry_hours', 48) === '48' ? 'selected' : '' }} selected>48 hours (2 days)</option>
                                <option value="72" {{ old('expiry_hours', 48) === '72' ? 'selected' : '' }}>72 hours (3 days)</option>
                                <option value="168" {{ old('expiry_hours', 48) === '168' ? 'selected' : '' }}>1 week</option>
                            </select>
                            @error('expiry_hours')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Info Box -->
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>How it works:</strong> Suppliers nearby will see your request and can submit offers. You can compare prices and quality, then choose the best supplier for your needs.
                        </div>
                    </div>

                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('owner.ingredient-requests') }}" class="btn btn-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-paper-plane me-2"></i>Post Request
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.form-label {
    color: #374151;
}

.fw-600 {
    font-weight: 600;
}
</style>
@endsection
