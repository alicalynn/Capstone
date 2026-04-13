# 🧪 KaPlato Application Testing Guide

## 🚀 Quick Start - Testing Your Application

### Prerequisites
- Node.js 18+ (for Angular frontend)
- PHP 8.2+ (for Laravel backend)
- MySQL 5.7+ (running)
- Composer installed

---

## **Step 1: Start Laravel Backend**

### Option A: Using PHP Built-in Server (Simple)
```bash
cd laravel-backend
php artisan serve
```
**Expected Output:**
```
Laravel development server started: http://127.0.0.1:8000
```

### Option B: Using Artisan with Custom Port
```bash
php artisan serve --host=localhost --port=8000
```

### Option C: For Network Access (Mobile Testing)
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

---

## **Step 2: Start Angular Frontend**

In a **new terminal window**:
```bash
cd KaPlato
npm install  # (only if dependencies not installed)
npm start
```

**Expected Output:**
```
✔ Compiled successfully.
Application bundle generated successfully.
Loopback address:127.0.0.1 is used for this request.
Local:   http://localhost:4200/
```

---

## **Step 3: Test the Application**

### 3.1 Health Check (Backend)
```bash
# Open in browser or use curl
curl http://localhost:8000/api/health
```

**Expected Response:**
```json
{
  "status": "Laravel backend is running!",
  "timestamp": "2026-04-13T12:00:00Z"
}
```

### 3.2 Open Frontend
```
http://localhost:4200
```

### 3.3 Test Login
1. Navigate to **Login page**
2. Try with test credentials:
   - **Email:** admin@kaplato.com
   - **Password:** admin123

**Expected:** Should login successfully (or show proper error if user doesn't exist)

### 3.4 Test API Calls
Try registering a new customer account through the frontend and verify:
- ✅ No hardcoded emergency login attempts
- ✅ Normal authentication flow
- ✅ Proper error messages

---

## **🧪 Automated API Tests**

### Test 1: Health Check
```bash
curl http://localhost:8000/api/health
```

### Test 2: Register New User
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "testuser@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "customer"
  }'
```

### Test 3: Login (Valid User)
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "password123"
  }'
```

**Expected Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "email": "testuser@example.com",
    "name": "Test User",
    "role": "customer"
  },
  "access_token": "your_token_here",
  "token_type": "Bearer"
}
```

### Test 4: Login (Invalid Credentials)
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "testuser@example.com",
    "password": "wrongpassword"
  }'
```

**Expected Response:** 401 Unauthorized

---

## **✅ Security Verification Checklist**

- [ ] **No emergency login** - No special endpoint for `alica@gmail.com`
- [ ] **Debug mode OFF** - No sensitive errors exposed
- [ ] **API keys secure** - Spoonacular key only in `.env`
- [ ] **Normal auth flow** - Login requires real credentials
- [ ] **Proper error messages** - No SQL errors or stack traces
- [ ] **CORS working** - Frontend can reach backend

---

## **🐛 Troubleshooting**

### Issue: "Connection refused"
**Solution:** Make sure Laravel backend is running on port 8000
```bash
php artisan serve
```

### Issue: "CORS error"
**Solution:** Check CORS is enabled in Laravel (`config/cors.php`)

### Issue: "Database connection error"
**Solution:** Verify MySQL is running and `.env` credentials are correct
```bash
# Test connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Issue: "Module not found" in Angular
**Solution:** Install dependencies
```bash
cd KaPlato
npm install
```

---

## **📊 Expected Test Results**

| Test | Expected Result | Status |
|------|-----------------|--------|
| Backend Health | 200 OK with status message | ✅ |
| Register User | 201 Created with token | ✅ |
| Login Success | 200 OK with access_token | ✅ |
| Login Fail | 401 Unauthorized | ✅ |
| No Emergency Login | ❌ 404 Not Found | ✅ |
| Frontend Load | App loads on localhost:4200 | ✅ |
| Security Headers | Proper headers set | ✅ |

---

## **📱 Mobile Testing (Optional)**

To test on physical device:

```bash
# Start backend on your machine IP
php artisan serve --host=YOUR_IP --port=8000

# Update KaPlato/src/environments/environment.ts
apiUrl: 'http://YOUR_IP:8000/api'

# Start frontend
npm start
```

Then access from mobile:
```
http://YOUR_IP:4200
```

---

## **🎯 Next Steps After Testing**

1. ✅ Verify all tests pass
2. ✅ Check browser console for errors (F12)
3. ✅ Check Laravel logs: `storage/logs/laravel.log`
4. ✅ Test key features (login, browse karenderias, etc.)
5. ✅ Ready for deployment!

---

**Need help?** Check the logs:
- Frontend: Browser DevTools Console (F12)
- Backend: `laravel-backend/storage/logs/laravel.log`
