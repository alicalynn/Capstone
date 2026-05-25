# Owner Confirmation Before Supplier Mark Delivered - Implementation

## Workflow
```
payment_confirmed 
    ↓
preparing 
    ↓
out_for_delivery 
    ↓
delivering 
    ↓
[OWNER CONFIRMS DELIVERY] ← owner_confirmed_delivery flag set to true
    ↓
[SUPPLIER CAN NOW MARK DELIVERED] ← Button enabled
    ↓
delivered ← Stock synced
```

## Changes Made

### Backend (Laravel)

#### 1. **New Migration File**
- **File:** `database/migrations/2026_05_25_add_owner_confirmed_delivery.php`
- **Purpose:** Adds two columns to `supply_orders` table:
  - `owner_confirmed_delivery` (boolean, default false)
  - `owner_confirmed_delivery_at` (timestamp, nullable)

#### 2. **SupplyOrder Model**
- **File:** `app/Models/SupplyOrder.php`
- **Changes:**
  - Added fields to `$fillable` array
  - Added casts for boolean and datetime types

#### 3. **SupplierWorkflowController**
- **File:** `app/Http/Controllers/SupplierWorkflowController.php`
- **Changes:**
  - Added permission check: Supplier cannot mark as delivered if `!owner_confirmed_delivery`
  - When owner confirms delivery (status='delivered'), sets:
    - `owner_confirmed_delivery = true`
    - `owner_confirmed_delivery_at = now()`
  - Added notification import and triggers `OwnerConfirmedDeliveryNotification` when owner confirms

#### 4. **New Notification Class**
- **File:** `app/Notifications/OwnerConfirmedDeliveryNotification.php`
- **Purpose:** Email notification sent to supplier when owner confirms delivery
- **Content:** Tells supplier they can now mark order as delivered

### Frontend (Angular/Ionic)

#### 1. **Inventory Management Template**
- **File:** `src/app/pages/inventory-management/inventory-management.page.html`
- **Changes:**
  - Updated "Mark Delivered" button condition:
    - Old: `[disabled]="order.status !== 'delivering'"`
    - New: `[disabled]="order.status !== 'delivering' || !order.owner_confirmed_delivery"`
  - Added tooltip when button is disabled by owner confirmation

## How to Deploy

### 1. Run Migration
```bash
cd Capstone/laravel-backend
php artisan migrate
```

### 2. Test Workflow
1. **Owner side:**
   - Navigate to Inventory Management
   - When order status is "delivering", click "Confirm Delivery"
   - This sets `owner_confirmed_delivery = true`

2. **Supplier side:**
   - Check email for "Order Delivery Confirmed" notification
   - The "Mark Delivered" button should now be enabled (no longer grayed out)
   - Click "Mark Delivered" to complete the order

### 3. Verify in Database
```sql
SELECT id, status, owner_confirmed_delivery, owner_confirmed_delivery_at 
FROM supply_orders 
WHERE id = <order_id>;
```

## Permission Logic

| User Role | Can Do |
|-----------|--------|
| **Supplier** | Mark as `delivered` ONLY if `owner_confirmed_delivery = true` and `status = 'delivering'` |
| **Owner** | Mark as `delivered` (confirms delivery) when `status = 'delivering'`, sets confirmation flag |

## Error Messages

If supplier tries to mark delivered before owner confirms:
```json
{
  "error": "Cannot mark as delivered until owner confirms delivery",
  "current_status": "delivering"
}
```

## Key Features
✅ Owner confirmation is required before supplier can mark delivered
✅ Supplier receives email notification when owner confirms
✅ Button disabled with helpful tooltip for UX
✅ Timestamp tracked for when owner confirmed
✅ Stock only syncs when supplier marks as delivered
