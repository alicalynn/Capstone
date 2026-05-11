# Supplier Request Posting Debug Guide

## Problem
Owner posts 3 ingredient requests but they don't appear in supplier dashboard.

## Root Cause Analysis

### 1. **Supplier API Filters** 
The endpoint `GET /api/ingredient-requests/supplier/available` has these filters:
```php
where('status', 'open')  // ✅ Requests must be "open"
->where('created_at', '>', now()->subHours(48))  // ✅ Must be posted within last 48 hours
```

### 2. **Debug Checklist**

#### A. Verify Requests Were Saved to Database
```bash
# In Laravel terminal, check database:
php artisan tinker
>>> DB::table('ingredient_requests')->get();
>>> DB::table('ingredient_requests')->where('karenderia_id', 1)->get();
```

**Expected output:** Should show 3 requests with:
- `status` = 'open'
- `created_at` = today's date
- `karenderia_id` = the owner's karenderia ID

#### B. Test the Supplier API Endpoint Directly
```bash
# In terminal, test the API:
curl -X GET "http://localhost:8000/api/ingredient-requests/supplier/available" \
  -H "Authorization: Bearer YOUR_SUPPLIER_TOKEN"
```

**Expected:** Should return JSON array with the 3 requests

#### C. Check Supplier Account Status
Verify supplier is verified:
```php
php artisan tinker
>>> $supplier = User::where('email', 'supplier@example.com')->first();
>>> dd($supplier->verified, $supplier->role, $supplier->application_status);
```

**Expected output:**
- `verified` = true
- `role` = 'supplier'
- `application_status` = 'approved'

#### D. Check Supplier Dashboard Loading
In browser console (when supplier is logged in):
```javascript
// Check if data is being loaded
console.log('currentUser:', localStorage.getItem('user_data'));
console.log('auth_token:', localStorage.getItem('auth_token'));

// Check network tab for API call
// Should see: GET /api/ingredient-requests/supplier/available
```

## Common Issues & Solutions

### Issue 1: Requests Not Saved
**Symptom:** Database shows 0 requests
**Solution:**
- Check `/owner/ingredient-requests/create` form works
- Verify form submits to `POST /owner/ingredient-requests`
- Check Laravel logs: `tail -f laravel-backend/storage/logs/laravel.log`

### Issue 2: Requests Saved But Not Returning from API
**Symptom:** Database has requests but API returns empty array
**Solutions:**
1. Check if requests have `status` = 'open' ✅
2. Check if requests were created recently (within 48 hours) ✅
3. Check if there's an authorization issue ❌

Verify by running:
```php
php artisan tinker
>>> IngredientRequest::where('status', 'open')->where('created_at', '>', now()->subHours(48))->count();
```

### Issue 3: Mobile App Not Loading Requests
**Symptom:** API works but supplier dashboard shows 0 requests
**Solutions:**
1. Check Network tab - is API call happening? ✅
2. Is API returning data? ✅
3. Is Angular parsing data correctly? ❌

Check in browser console:
```javascript
// Simulate the API call from Angular
fetch('/api/ingredient-requests/supplier/available', {
  headers: { 'Authorization': `Bearer ${localStorage.getItem('auth_token')}` }
})
.then(r => r.json())
.then(data => console.log('API Response:', data));
```

## Step-by-Step Debug Process

### Step 1: Owner Posts Request
- Navigate to `/owner/ingredient-requests/create`
- Fill form with 3 requests
- Click "Post" button
- Verify page redirects to requests list

### Step 2: Verify Database
```bash
cd laravel-backend
php artisan tinker
>>> IngredientRequest::latest()->limit(3)->get();
```
**✓ If requests appear, go to Step 3**
**✗ If not, issue is in store() method or form**

### Step 3: Test API Endpoint
```bash
# Get a supplier's token first
curl -X POST "http://localhost:8000/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"email":"supplier@example.com","password":"password"}'

# Then test the supplier endpoint
curl -X GET "http://localhost:8000/api/ingredient-requests/supplier/available" \
  -H "Authorization: Bearer PASTE_TOKEN_HERE"
```
**✓ If requests appear in response, go to Step 4**
**✗ If empty, issue is in supplierIndex() filtering**

### Step 4: Check Mobile App
- Supplier logs into mobile app
- Navigate to `/supplier-home`
- Click "Requests" tab
- Open browser console (F12)
- Check Network tab for API call
- Check Console for errors

**✓ If requests appear, working!**
**✗ If not, issue is in Angular component**

## If Still Stuck...

Print out the following info:

**1. Owner Info:**
```php
php artisan tinker
>>> $owner = User::role('karenderia_owner')->first();
>>> dd($owner->id, $owner->email);
```

**2. Karenderia Info:**
```php
>>> $karenderia = Karenderia::first();
>>> dd($karenderia->id, $karenderia->business_name, $karenderia->owner_id);
```

**3. Posted Requests:**
```php
>>> $requests = IngredientRequest::latest()->limit(3)->get();
>>> dd($requests->map(fn($r) => ['id' => $r->id, 'title' => $r->title, 'status' => $r->status, 'created_at' => $r->created_at]));
```

**4. Supplier Info:**
```php
>>> $supplier = User::where('role', 'supplier')->first();
>>> dd($supplier->id, $supplier->email, $supplier->verified, $supplier->application_status);
```

Then share the output with me!
