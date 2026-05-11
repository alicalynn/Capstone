# 🚀 Quick Start Guide - Supplier Request System

## What Was Built

You now have a **complete ingredient request matching system** where:
- ✅ Karenderia owners **POST ingredient requests**
- ✅ Suppliers **BROWSE nearby requests**
- ✅ Suppliers **SUBMIT competitive quotes**
- ✅ Owners **COMPARE & SELECT** the best supplier
- ✅ **MESSAGING SYSTEM** for owner-supplier communication (text + call)

---

## 🔧 Setup Steps

### Step 1: Run Database Migrations
```bash
cd laravel-backend
php artisan migrate
```

This creates 3 new tables:
- `ingredient_requests` - Stores all requests
- `supplier_quotes` - Stores supplier offers
- `messages` - Stores messages/chat history

### Step 2: Update Karenderia Model (if needed)
The system is linked to existing Karenderia model. No changes needed.

---

## 📱 Testing the System

### **For Web Interface (Owner Side)**

1. Login as Karenderia Owner
2. Navigate to: `/owner/ingredient-requests`
3. Click "Post New Request"
4. Fill in ingredient details
5. Submit → Request goes live to suppliers

### **For API Testing (Postman/Curl)**

#### **Owner: Create Ingredient Request**
```bash
curl -X POST http://localhost:8000/api/ingredient-requests \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Chicken Breast",
    "description": "Fresh, high quality",
    "ingredient_type": "Meat",
    "needed_quantity": "5",
    "unit": "kg",
    "needed_by_date": "2026-05-20",
    "budget": "750",
    "delivery_address": "123 Main St, Bacolod",
    "expiry_hours": 48
  }'
```

#### **Supplier: View Available Requests**
```bash
curl -X GET "http://localhost:8000/api/ingredient-requests/supplier/available" \
  -H "Authorization: Bearer SUPPLIER_TOKEN"
```

#### **Supplier: Submit a Quote**
```bash
curl -X POST http://localhost:8000/api/supplier-quotes \
  -H "Authorization: Bearer SUPPLIER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "ingredient_request_id": 1,
    "price_per_unit": "140",
    "available_quantity": "10",
    "unit": "kg",
    "delivery_date": "2026-05-18",
    "delivery_method": "delivery",
    "notes": "Fresh batch arriving tomorrow"
  }'
```

#### **Owner: Accept a Quote**
```bash
curl -X PATCH http://localhost:8000/api/supplier-quotes/1/accept \
  -H "Authorization: Bearer OWNER_TOKEN"
```

#### **Both: Send a Message**
```bash
curl -X POST http://localhost:8000/api/messages \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "to_user_id": 5,
    "ingredient_request_id": 1,
    "message": "Can you deliver by 10am tomorrow?",
    "type": "text"
  }'
```

#### **Both: Request a Phone Call**
```bash
curl -X POST http://localhost:8000/api/messages/call-request \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "to_user_id": 5,
    "ingredient_request_id": 1,
    "call_phone_number": "09123456789"
  }'
```

---

## 📡 API Endpoints Summary

### **Owner-Only Endpoints**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/ingredient-requests` | Create request |
| GET | `/api/ingredient-requests/owner/my-requests` | View my requests |
| GET | `/api/ingredient-requests/owner/{id}` | View request + quotes |
| PATCH | `/api/ingredient-requests/{id}/status` | Update status |
| GET | `/api/supplier-quotes/{request_id}/all` | View all quotes for request |
| PATCH | `/api/supplier-quotes/{quote}/accept` | Accept a quote |
| PATCH | `/api/supplier-quotes/{quote}/reject` | Reject a quote |

### **Supplier-Only Endpoints**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| GET | `/api/ingredient-requests/supplier/available` | Browse open requests |
| GET | `/api/ingredient-requests/supplier/{id}` | View request details |
| POST | `/api/supplier-quotes` | Submit quote |
| GET | `/api/supplier-quotes/my-quotes` | View my quotes |

### **Both Can Use**

| Method | Endpoint | Purpose |
|--------|----------|---------|
| POST | `/api/messages` | Send message |
| GET | `/api/messages/conversations` | Get all conversations |
| GET | `/api/messages/ingredient-requests/{id}` | Get conversation for request |
| GET | `/api/messages/unread` | Get unread count |
| POST | `/api/messages/call-request` | Request phone call |

---

## 🌐 Web Interface Routes (Owner)

| Route | Purpose |
|-------|---------|
| `/owner/ingredient-requests` | List your requests |
| `/owner/ingredient-requests/create` | Post new request |
| `/owner/ingredient-requests/{id}` | View request + supplier quotes |

---

## 🗂️ Files Location Reference

| Component | Location |
|-----------|----------|
| Models | `app/Models/IngredientRequest.php`, `SupplierQuote.php`, `Message.php` |
| API Controllers | `app/Http/Controllers/IngredientRequestController.php`, etc. |
| Web Controllers | `app/Http/Controllers/Web/OwnerIngredientRequestController.php`, etc. |
| Views | `resources/views/owner/ingredient-requests/` |
| Migrations | `database/migrations/2026_05_11_*.php` |
| Middleware | `app/Http/Middleware/SupplierVerifiedMiddleware.php` |

---

## ✨ Key Features Explained

### **Request Posting**
- Owner specifies exactly what they need
- Sets deadline, budget, quantity
- Request stays open for specified hours (24/48/72 hours or 1 week)
- Auto-expires after expiry period

### **Multiple Quotes**
- Many suppliers can submit offers for same request
- Each supplier can only submit ONE quote per request
- Owner sees all quotes side-by-side for comparison
- No limit on number of suppliers responding

### **Smart Acceptance**
- When owner accepts ONE quote:
  - That quote status → "accepted"
  - All other pending quotes → "rejected" (auto)
  - Request status → "accepted"
  - Owner can now message that supplier

### **Messaging**
- Text messages for questions/coordination
- Call requests with phone number
- Full conversation history
- Unread message tracking

---

## 🎯 Next: Mobile App Integration

To connect this with your Ionic mobile app:

1. **Add API calls** to your supplier/owner modules
2. **Call endpoints** from mobile:
   ```typescript
   // Supplier browsing requests
   this.http.get('/api/ingredient-requests/supplier/available')
   
   // Submit quote
   this.http.post('/api/supplier-quotes', quoteData)
   
   // Owner viewing requests
   this.http.get('/api/ingredient-requests/owner/my-requests')
   ```

3. **Add real-time notifications** (optional):
   - When supplier submits quote → Notify owner
   - When owner accepts → Notify supplier
   - Use Pusher/WebSocket for real-time updates

---

## ⚠️ Important Notes

✅ All endpoints require authentication (`auth:sanctum`)
✅ Suppliers must be verified to access requests
✅ Owners must have approved karenderia to post requests
✅ One supplier per accepted quote (prevents conflicts)
✅ Requests auto-expire (prevents stale listings)
✅ Messages linked to requests (maintains context)

---

## 🚨 Troubleshooting

**Q: Migration fails**
A: Make sure you ran `php artisan migrate` from `laravel-backend` directory

**Q: Can't see requests as supplier**
A: Check if your user account is marked as "supplier" with "verified" status

**Q: Quote not accepting**
A: Request might be closed. Only "open" requests can accept new quotes.

**Q: Middleware error**
A: Clear Laravel cache: `php artisan config:cache`

---

## 📞 System is Ready!

You can now:
1. ✅ Post ingredient requests (as owner)
2. ✅ Browse & quote on requests (as supplier)
3. ✅ Compare and select suppliers
4. ✅ Chat with suppliers
5. ✅ Coordinate deliveries

**Run migrations and start testing!** 🚀
