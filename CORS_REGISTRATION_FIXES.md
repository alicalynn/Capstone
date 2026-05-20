# Supplier Registration CORS & Authentication Fixes
## May 20, 2026

### Issues Resolved

#### 1. **CORS Error on Supplier Registration** ✅ FIXED
**Error:** `Access to fetch at 'http://localhost:8000/api/auth/register-supplier' from origin 'http://localhost:8100' has been blocked by CORS policy: No 'Access-Control-Allow-Origin' header is present`

**Root Cause:**
- CORS middleware wasn't properly configured for all request types
- Missing exception handler to add CORS headers to error responses
- No explicit allowed origins list

**Fixes Applied:**

**A. Updated `CorsMiddleware.php`**
```php
// Enhanced with:
- Explicit allowed origins list (localhost:8100, 127.0.0.1:8100, etc.)
- Credentials support for Sanctum authentication
- Exception handling to ensure CORS headers on error responses
- Proper OPTIONS preflight request handling
- Expose-Headers for better compatibility
```

**B. Updated `bootstrap/app.php`**
```php
// Added exception handler:
->withExceptions(function (Exceptions $exceptions): void {
    $exceptions->respond(function (\Illuminate\Http\Response $response) {
        // Adds CORS headers to ALL responses, including errors
    });
})
```

---

#### 2. **403/500 Errors on Protected Endpoints** ✅ FIXED
**Errors:**
- `403 Forbidden` on `/api/inventory`
- `500 Internal Server Error` on `/api/messages/conversations`

**Root Cause:**
- Supplier registration logs out user (correct for pending approval)
- Supplier home page immediately tries to load protected endpoints
- No auth token present = authentication failure

**Fixes Applied:**

**A. Updated `auth.service.ts`**
```typescript
registerSupplier(registrationData: any): Observable<any> {
  return this.http.post<any>(`${this.apiUrl}/auth/register-supplier`, registrationData)
    .pipe(
      tap(response => {
        // Clear auth tokens after successful registration
        // User must login after admin approval
        this.logout();
      }),
      catchError(error => {
        console.error('Supplier registration error:', error);
        throw error;
      })
    );
}
```

**B. Updated `supplier-home.page.ts`**
```typescript
ngOnInit() {
  // Check authentication before loading protected data
  if (!this.authService.isAuthenticated()) {
    console.log('User not authenticated, redirecting to login');
    this.router.navigate(['/login']);
    return;
  }
  this.loadDashboardData();
}
```

---

### Testing Checklist

#### Backend (Laravel)
- [ ] Verify CORS headers are present in all responses
  ```bash
  # Test OPTIONS request
  curl -X OPTIONS http://localhost:8000/api/auth/register-supplier \
    -H "Origin: http://localhost:8100" \
    -H "Access-Control-Request-Method: POST" \
    -v
  ```
  
- [ ] Check for CORS headers in response headers:
  - `Access-Control-Allow-Origin: http://localhost:8100`
  - `Access-Control-Allow-Credentials: true`
  - `Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS`

#### Frontend (Mobile App)
- [ ] Test supplier registration flow:
  1. Go to Register page
  2. Select "Supplier" account type
  3. Fill in all required fields:
     - Username
     - Email
     - Password & Confirm Password
     - Phone Number (optional)
     - Address (optional)
  4. Submit registration

- [ ] Expected behavior:
  - No CORS error in console
  - Success message displayed
  - Auto-redirect to login after 2 seconds
  - No 403/500 errors in console

- [ ] After registration:
  1. Go to login page
  2. Wait for admin approval (in test environment, you can approve manually)
  3. Login with registered credentials
  4. Supplier home page loads without errors
  5. Inventory, Requests, and Messages sections load successfully

---

### Troubleshooting Guide

#### If CORS Error Still Occurs:
1. **Check allowed origins in CorsMiddleware.php**
   - Verify your mobile app's actual origin is in the `$allowedOrigins` array
   - If using different IP/port, add it to the list

2. **Clear browser/app cache**
   - Browser: Clear cache and reload
   - Mobile: Clear app cache or uninstall/reinstall

3. **Check Laravel logs**
   ```bash
   cd laravel-backend
   tail -f storage/logs/laravel.log
   ```

4. **Verify CORS headers manually**
   ```bash
   curl -X POST http://localhost:8000/api/auth/register-supplier \
     -H "Origin: http://localhost:8100" \
     -H "Content-Type: application/json" \
     -v
   ```

#### If 403/500 Errors After Login:
1. **Verify auth token is being stored**
   - Check browser DevTools → Application → LocalStorage
   - Should have `auth_token` key with JWT token

2. **Verify Authorization header is sent**
   - DevTools → Network → Select request
   - Check Headers section for `Authorization: Bearer [token]`

3. **Check for endpoint issues**
   - `/api/inventory` - requires auth:sanctum middleware
   - `/api/messages/conversations` - requires auth:sanctum middleware
   - Verify endpoints exist in `routes/api.php`

---

### Files Modified

1. **Backend:**
   - `laravel-backend/app/Http/Middleware/CorsMiddleware.php` - Enhanced CORS handling
   - `laravel-backend/bootstrap/app.php` - Added exception handler for CORS

2. **Frontend:**
   - `KaPlato/src/app/services/auth.service.ts` - Fixed registerSupplier() method
   - `KaPlato/src/app/pages/supplier-home/supplier-home.page.ts` - Added auth check

---

### Additional Improvements Made

#### Security
- Added explicit allowed origins (no wildcard `*` for production)
- Proper CORS credentials handling for Sanctum

#### User Experience
- Clear authentication flow for pending registrations
- Redirect to login if accessing protected pages without auth
- Consistent logout behavior across all registration types

#### Debugging
- Enhanced error logging in CORS middleware
- Console messages for auth state changes
- Better error handling with try-catch

---

### Next Steps

1. **Test the complete registration flow** (see Testing Checklist)
2. **Monitor logs** during testing for any remaining issues
3. **Adjust allowed origins** if needed for different deployment scenarios
4. **Consider using config file** for allowed origins instead of hardcoding

```php
// Future improvement: Use config for origins
protected $allowedOrigins = config('cors.allowed_origins', [
    'http://localhost:8100',
    // ...
]);
```

---

### Support

For issues not covered here:
1. Check browser console for errors
2. Check Laravel logs: `laravel-backend/storage/logs/laravel.log`
3. Enable DEBUG mode in `.env` for more detailed error messages
