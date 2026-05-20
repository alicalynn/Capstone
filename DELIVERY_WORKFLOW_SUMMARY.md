# Supplier-Owner Delivery Workflow - Complete System Overview

## 🎯 Problem Solved

**Before:** When a supplier confirmed an order, the owner's inventory was immediately updated, even though delivery hadn't happened yet. If delivery failed, the inventory would be inaccurate.

**After:** Inventory is only updated when the supplier marks the order as "delivered" and provides proof (signature/photos). This ensures accurate stock tracking.

---

## 📦 What's Been Implemented

### 1. **Enhanced Database Schema** 
`database/migrations/2026_05_20_000001_enhance_supply_orders_with_delivery_tracking.php`

New fields added to `supply_orders` table:
- Payment tracking: `payment_status`, `payment_date`, `payment_method`, `payment_reference`
- Delivery details: `delivery_method`, `delivery_address`, `delivery_coordinates`
- Timeline: `confirmed_at`, `shipped_at`, `out_for_delivery_at`, `delivered_at`
- Proof: `delivery_signature_url`, `photo_proof_urls`, `delivery_notes`, `delivered_by_name`
- Retry logic: `status_history`, `failed_reason`, `retry_count`, `max_retries`

### 2. **Enhanced Model**
`app/Models/SupplyOrder.php`

New helper methods:
- `getStatusTimeline()` - Returns visual timeline of all statuses
- `getNextPossibleStatuses()` - Returns valid next statuses
- `isTerminal()` - Check if order is complete
- `canBeRetried()` - Check if failed delivery can be retried
- `recordStatusChange()` - Audit trail of status changes

### 3. **Enhanced Controller**
`app/Http/Controllers/SupplierWorkflowController.php`

Updated/New methods:
- `updateOrderStatus()` - REBUILT with complete workflow support
- `getOrderDetail()` - NEW endpoint to get order with timeline

### 4. **New API Routes**
`routes/api.php`

```
GET    /api/supply/orders/{orderId}                    # Get order detail with timeline
PATCH  /api/supply/orders/{orderId}/status             # Update order status
```

---

## 🔄 Complete Workflow

### Order Lifecycle

```
STAGE 1: ORDERING
├─ Owner selects items from marketplace
├─ Creates supply order with items
└─ Status: pending
   └─ NO stock update yet

STAGE 2: CONFIRMATION
├─ Supplier reviews order
├─ Supplier confirms they'll fulfill
└─ Status: confirmed
   └─ NO stock update yet

STAGE 3: PAYMENT
├─ Payment processed (GCash, Bank, COD)
├─ Supplier records payment
└─ Status: payment_confirmed
   └─ NO stock update yet

STAGE 4: FULFILLMENT
├─ Supplier prepares items
└─ Status: preparing
   └─ NO stock update yet

STAGE 5: SHIPPING
├─ Items packed and labeled
├─ Handed to courier
└─ Status: shipped
   └─ NO stock update yet

STAGE 6: TRANSIT
├─ In transit to destination (optional)
├─ Out for delivery with driver info
└─ Status: out_for_delivery
   └─ NO stock update yet

STAGE 7: DELIVERY ✅
├─ Supplier uploads proof (signature/photos)
├─ Marks as delivered
└─ Status: delivered
   └─ ✅ OWNER'S INVENTORY UPDATED!
   └─ Stock added to kitchen

STAGE 8: COMPLETE
└─ Order finished, stock in owner's system
```

### Failure Scenario

```
At out_for_delivery...
   ↓
DELIVERY FAILED ❌
   ├─ Record failure reason
   ├─ Increment retry counter
   └─ Status: delivery_failed
      └─ NO stock update
      
Can retry?
   ├─ If retry_count < max_retries (3)
   │  └─ Retry: status → out_for_delivery (try again)
   │     └─ If successful → delivered → stock updates ✅
   │
   └─ If max retries exceeded
      └─ Cancel order
         └─ Restore supplier stock
         └─ NO owner stock update
```

---

## 📊 Status Summary Table

| Status | Supplier Action | Owner Sees | Stock Updated | Can Proceed | Terminal |
|--------|-----------------|-----------|--------------|------------|----------|
| pending | Awaiting confirmation | Order placed | ❌ No | Yes | No |
| confirmed | Order accepted | Order confirmed | ❌ No | Yes | No |
| payment_confirmed | Payment received | Payment done | ❌ No | Yes | No |
| preparing | Packing items | Being prepared | ❌ No | Yes | No |
| shipped | Left warehouse | On the way | ❌ No | Yes | No |
| in_transit | Moving to destination | In transit | ❌ No | Yes | No |
| out_for_delivery | With driver/courier | Out for delivery | ❌ No | Yes | No |
| delivered | Proof uploaded | ✅ Received! | ✅ YES ✅ | No | Yes |
| delivery_failed | Reason recorded | Delivery failed | ❌ No | If retries < max | No |
| cancelled | Order cancelled | Order cancelled | ❌ No | No | Yes |

---

## 🧪 Quick Start Testing

### 1. Run Migration
```bash
cd laravel-backend
php artisan migrate
```

### 2. Test API Flow

**Create order:**
```bash
curl -X POST http://localhost:8000/api/supply/orders \
  -H "Authorization: Bearer {owner_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "items": [{"supplier_inventory_item_id": 1, "quantity": 50}],
    "delivery_date": "2026-05-21"
  }'
```

**Get order with timeline:**
```bash
curl -X GET http://localhost:8000/api/supply/orders/1 \
  -H "Authorization: Bearer {token}"
```

**Update status (supplier confirms):**
```bash
curl -X PATCH http://localhost:8000/api/supply/orders/1/status \
  -H "Authorization: Bearer {supplier_token}" \
  -H "Content-Type: application/json" \
  -d '{"status": "confirmed"}'
```

**Mark as delivered (stock updates!):**
```bash
curl -X PATCH http://localhost:8000/api/supply/orders/1/status \
  -H "Authorization: Bearer {supplier_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "delivered",
    "delivery_notes": "Left at kitchen entrance",
    "delivery_signature_url": "https://example.com/sig.jpg"
  }'
```

### 3. Verify Stock Updated
```bash
curl -X GET http://localhost:8000/api/inventory \
  -H "Authorization: Bearer {owner_token}"
```

Items should now appear in owner's inventory!

---

## 📱 Frontend Integration Points

### For Karenderia Owner App
1. **Orders List Page**
   - Display all orders with status badges
   - Color-code by status
   - Filter by status (pending, in_transit, delivered)

2. **Order Detail Page**
   - Show timeline with all statuses
   - Current step highlighted
   - Delivery driver info when available
   - Estimated vs actual delivery time

3. **Stock Update Trigger**
   - Show when stock was added (on delivery)
   - Link order to inventory items
   - Show cost and quantity added

### For Supplier App
1. **Orders Dashboard**
   - Filter by status
   - Bulk action buttons for status updates
   - Quick confirm/ship/deliver buttons

2. **Status Update Forms**
   - Simple buttons for straightforward updates
   - Forms for delivery (address, driver name, notes)
   - File upload for proof (signature/photos)
   - Retry dialog for failed deliveries

---

## 🔐 Permission Model

### Supplier Can:
- ✅ View their orders
- ✅ Update status forward (pending → confirmed → ... → delivered)
- ✅ Upload delivery proof
- ✅ Record payment received
- ✅ Handle delivery failures and retries

### Owner Can:
- ✅ View their orders
- ✅ See status timeline
- ✅ Cancel order (at any stage)
- ❌ Cannot change status forward
- ❌ Cannot update delivery info

---

## 📈 Key Improvements

### Before Implementation
```
Order Created
    ↓
Supplier Confirms
    ↓
⚠️ INVENTORY UPDATED (might fail in delivery!)
    ↓
Delivery (might fail)
    ↓
Problem: Inventory shows items that aren't actually there
```

### After Implementation
```
Order Created (pending)
    ↓
Supplier Confirms (confirmed)
    ↓
Payment Processed (payment_confirmed)
    ↓
Supplier Prepares (preparing)
    ↓
Supplier Ships (shipped)
    ↓
Out for Delivery (out_for_delivery)
    ↓
✅ DELIVERY CONFIRMED WITH PROOF
    ↓
✅ INVENTORY UPDATED (certain to succeed)
    ↓
Solution: Accurate inventory at all times
```

---

## 🔍 Audit Trail

Every status change is recorded in `status_history` JSON:
```json
[
  {
    "from_status": "pending",
    "to_status": "confirmed",
    "changed_at": "2026-05-20T08:15:00Z",
    "reason": null
  },
  {
    "from_status": "confirmed",
    "to_status": "payment_confirmed",
    "changed_at": "2026-05-20T08:30:00Z",
    "reason": null
  },
  {
    "from_status": "out_for_delivery",
    "to_status": "delivery_failed",
    "changed_at": "2026-05-20T16:00:00Z",
    "reason": "Owner not home"
  }
]
```

Enables:
- Complete order history
- Identify bottlenecks
- Calculate average delivery time
- Audit for disputes

---

## 📋 Files Modified/Created

### Created:
1. `database/migrations/2026_05_20_000001_enhance_supply_orders_with_delivery_tracking.php`
2. `DELIVERY_WORKFLOW_DESIGN.md` - Complete design document
3. `DELIVERY_IMPLEMENTATION_GUIDE.md` - Implementation guide with examples

### Modified:
1. `app/Models/SupplyOrder.php` - Added helper methods
2. `app/Http/Controllers/SupplierWorkflowController.php` - Rebuilt status logic
3. `routes/api.php` - Added new endpoint

---

## 🚀 Next Steps

1. **Run Migration**
   ```bash
   php artisan migrate
   ```

2. **Test API Endpoints**
   - Use postman collection or cURL commands

3. **Update Frontend**
   - Implement status timeline component
   - Add status update buttons/forms
   - Show inventory update confirmation

4. **Add Notifications**
   - Email/SMS on each status change
   - In-app notifications in real-time

5. **Monitor & Optimize**
   - Track average delivery times
   - Identify failure patterns
   - Optimize delivery routes

---

## ⚠️ Critical Notes

### ✅ DO THIS:
- Only update owner inventory when status === "delivered"
- Always request delivery proof before marking delivered
- Use status history for auditing
- Validate all status transitions
- Record failure reasons for retry logic

### ❌ DON'T DO THIS:
- ❌ Update inventory on "confirmed" status
- ❌ Skip delivery proof requirement
- ❌ Allow cancellation after delivery
- ❌ Skip permission checks
- ❌ Allow invalid status transitions

---

## 📞 Support & Troubleshooting

### Common Issues

**Q: Why is my stock not updating?**
- A: Ensure order status is exactly "delivered" and you have `delivered_at` timestamp set

**Q: Can I retry a delivery?**
- A: Yes, if `retry_count < max_retries` (default: 3). Status must be "delivery_failed"

**Q: What if supplier cancels after shipping?**
- A: Mark as "delivery_failed", owner can choose to cancel (no stock added)

**Q: How do I know when owner received stock?**
- A: Check `delivered_at` timestamp in order and verify in owner's inventory

---

## 📚 Related Documentation

- `DELIVERY_WORKFLOW_DESIGN.md` - Conceptual design
- `DELIVERY_IMPLEMENTATION_GUIDE.md` - Step-by-step implementation
- API Examples in this guide

**Current Date:** May 20, 2026
**System Version:** Enhanced Delivery Workflow v1.0
