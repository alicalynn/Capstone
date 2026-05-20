# Supplier-Owner Delivery Workflow System Design
## Comprehensive Order-to-Delivery Process

---

## 📋 Current Status

### Existing Database Schema
**supply_orders table:**
- id
- karenderia_id (FK)
- supplier_id (FK)
- status (pending, confirmed, delivered, cancelled)
- total_amount
- notes
- delivery_date
- timestamps

**supply_order_items table:**
- id
- supply_order_id (FK)
- supplier_inventory_item_id (FK)
- quantity
- unit_price
- line_total
- timestamps

### Current Workflow (Limited)
1. **Pending** - Order placed by owner, waiting for supplier confirmation
2. **Confirmed** - Stock automatically synced to owner's inventory (⚠️ Problem: happens at confirmation, not delivery)
3. **Delivered** - Order marked complete
4. **Cancelled** - Order cancelled, stock restored

---

## 🔴 Current Issues

### 1. **Premature Stock Update**
- Stock is updated when order is "confirmed" (payment)
- Should only update when delivery is actually completed
- Causes inventory discrepancies if delivery fails

### 2. **No Delivery Tracking**
- Missing intermediate statuses:
  - Payment confirmed
  - Shipped/Ready for pickup
  - In transit/Out for delivery
  - Delivered
  - Delivery failed
- No delivery address or method tracking
- No actual vs estimated delivery time
- No proof of delivery

### 3. **No Visibility into Order Status**
- Owner can't see when supplier confirms
- Owner can't see when item ships
- Owner can't track delivery in real-time

---

## ✅ Proposed Solution

### Enhanced Status Flow

```
Owner Places Order (pending)
    ↓
Supplier Confirms Order (confirmed)
    ↓
Payment Processed (payment_confirmed)
    ↓
Supplier Prepares/Picks Items (preparing)
    ↓
Order Ships (shipped)
    ↓
In Transit (in_transit)
    ↓
Out for Delivery (out_for_delivery)
    ↓
Delivered to Owner (delivered) ← ONLY HERE: Stock Updated!
    ↓
Order Complete
```

### Optional Status for Failures
```
Any Stage → Delivery Failed (delivery_failed)
         → Can retry or cancel
```

---

## 🗄️ Database Schema Enhancements

### New Migration Required

```sql
ALTER TABLE supply_orders ADD COLUMN (
    -- Payment Status
    payment_status VARCHAR(50) DEFAULT 'pending',  -- pending, confirmed, failed
    payment_date TIMESTAMP NULL,
    payment_method VARCHAR(100) NULL,
    payment_reference VARCHAR(255) NULL,
    
    -- Delivery Tracking
    delivery_method VARCHAR(100) NULL,  -- pickup, delivery, courier
    delivery_address TEXT NULL,
    delivery_coordinates JSON NULL,
    
    -- Delivery Timeline
    confirmed_at TIMESTAMP NULL,
    shipped_at TIMESTAMP NULL,
    out_for_delivery_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    
    -- Delivery Proof
    delivery_notes TEXT NULL,
    delivered_by_name VARCHAR(255) NULL,  -- Driver/Courier name
    delivery_signature_url VARCHAR(255) NULL,
    photo_proof_urls JSON NULL,  -- Array of delivery photos
    
    -- Status History (for audit trail)
    status_history JSON NULL,
    
    -- Retry logic
    failed_reason VARCHAR(255) NULL,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3
);

ALTER TABLE supply_orders MODIFY COLUMN status ENUM(
    'pending', 
    'confirmed', 
    'payment_confirmed',
    'preparing', 
    'shipped', 
    'in_transit',
    'out_for_delivery',
    'delivered', 
    'delivery_failed',
    'cancelled'
) DEFAULT 'pending';

-- Add indexes for faster queries
CREATE INDEX idx_supply_orders_status ON supply_orders(status);
CREATE INDEX idx_supply_orders_payment_status ON supply_orders(payment_status);
CREATE INDEX idx_supply_orders_timestamps ON supply_orders(confirmed_at, delivered_at);
```

---

## 🔄 Updated Workflow Logic

### Step 1: Owner Places Order
```php
// Status: pending
$order = SupplyOrder::create([
    'karenderia_id' => $karenderia->id,
    'supplier_id' => $supplier->id,
    'status' => 'pending',
    'total_amount' => $total,
    'payment_status' => 'pending',
]);

// Stock is RESERVED but NOT added to owner inventory yet
// Supplier's available_stock is decremented (already done in current code)
```

### Step 2: Supplier Confirms Order
```php
// Status: confirmed
$order->update([
    'status' => 'confirmed',
    'confirmed_at' => now(),
]);

// Stock still NOT added to owner inventory
// Just confirmation from supplier that they'll fulfill
```

### Step 3: Payment Processed
```php
// Status: payment_confirmed
$order->update([
    'status' => 'payment_confirmed',
    'payment_status' => 'confirmed',
    'payment_date' => now(),
    'payment_method' => 'gcash', // or 'bank_transfer', 'onsite'
    'payment_reference' => $transactionId,
]);

// Stock still NOT added to owner inventory
```

### Step 4: Supplier Prepares Order
```php
// Status: preparing
$order->update([
    'status' => 'preparing',
    'delivery_method' => 'delivery', // or 'pickup'
    'delivery_address' => $address,
]);
```

### Step 5: Order Ships
```php
// Status: shipped
$order->update([
    'status' => 'shipped',
    'shipped_at' => now(),
]);

// Send notification to owner: "Order on its way!"
```

### Step 6: Out for Delivery
```php
// Status: out_for_delivery
$order->update([
    'status' => 'out_for_delivery',
    'out_for_delivery_at' => now(),
    'delivered_by_name' => 'John Driver',
]);

// Owner gets notification with driver info
```

### Step 7: Delivery Complete ✅
```php
// Status: delivered
// *** THIS IS WHERE STOCK GETS UPDATED ***

DB::transaction(function () use ($order) {
    $order->update([
        'status' => 'delivered',
        'delivered_at' => now(),
        'delivery_notes' => $notes,
        'delivery_signature_url' => $signatureUrl,
        'photo_proof_urls' => $photoUrls,
    ]);
    
    // NOW update owner's inventory
    syncKitchenStockFromSupplyOrder($order, true);
    
    // Log transaction as complete
    recordPaymentTransaction($order);
});

// Send notification to owner: "Order received! Stock updated"
```

### Step 8 (Optional): Delivery Failed
```php
// Status: delivery_failed
$order->update([
    'status' => 'delivery_failed',
    'failed_reason' => 'Owner not home',
    'retry_count' => $order->retry_count + 1,
]);

// Owner gets notification to accept redelivery
// if retry_count < max_retries, allow retry
```

---

## 📊 Status Tracking Views

### For Karenderia Owner View
```json
{
  "order_id": 1,
  "supplier": "ABC Suppliers",
  "status": "out_for_delivery",
  "status_timeline": [
    { "status": "pending", "time": "2026-05-20 08:00", "icon": "✓" },
    { "status": "confirmed", "time": "2026-05-20 08:15", "icon": "✓" },
    { "status": "payment_confirmed", "time": "2026-05-20 08:30", "icon": "✓" },
    { "status": "preparing", "time": "2026-05-20 09:00", "icon": "✓" },
    { "status": "shipped", "time": "2026-05-20 10:00", "icon": "✓" },
    { "status": "out_for_delivery", "time": "2026-05-20 14:30", "icon": "🕐" },
    { "status": "delivered", "time": null, "icon": "○" }
  ],
  "current_step": {
    "status": "out_for_delivery",
    "description": "Your order is out for delivery",
    "delivered_by": "John (Driver #123)",
    "estimated_arrival": "2026-05-20 16:00",
    "delivery_address": "123 Main St, Bacolod"
  },
  "items": [
    { "item": "Tomatoes", "qty": "50 kg", "price": 500 }
  ],
  "total": 500,
  "payment_status": "confirmed"
}
```

### For Supplier View
```json
{
  "order_id": 1,
  "owner": "Alica's Kitchen",
  "status": "out_for_delivery",
  "actions_available": ["view_tracking", "confirm_delivery"],
  "delivery_info": {
    "address": "123 Main St, Bacolod",
    "method": "courier",
    "driver": "John",
    "phone": "09123456789"
  }
}
```

---

## 🛠️ Implementation Steps

### Phase 1: Database Migration
1. Create migration file for new columns
2. Add status enum options
3. Create indexes

### Phase 2: Update Models
```php
// SupplyOrder.php
class SupplyOrder extends Model {
    protected $fillable = [
        // ... existing
        'payment_status', 'payment_date', 'payment_method',
        'delivery_method', 'delivery_address',
        'confirmed_at', 'shipped_at', 'delivered_at',
        'delivery_notes', 'delivered_by_name',
        'status_history', 'failed_reason'
    ];
    
    protected $casts = [
        'delivery_coordinates' => 'json',
        'status_history' => 'json',
        'photo_proof_urls' => 'json',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];
    
    // Helper methods
    public function getStatusTimeline() { /* ... */ }
    public function canBeRetried() { /* ... */ }
    public function getNextPossibleStatuses() { /* ... */ }
}
```

### Phase 3: Update Controller
```php
// SupplierWorkflowController.php
public function updateOrderStatus(Request $request, int $orderId) {
    $validated = $request->validate([
        'status' => 'required|in:confirmed,payment_confirmed,preparing,shipped,in_transit,out_for_delivery,delivered,delivery_failed,cancelled',
        'delivery_method' => 'sometimes|in:pickup,delivery,courier',
        'delivery_address' => 'sometimes|string',
        'delivered_by_name' => 'sometimes|string',
        'delivery_notes' => 'sometimes|string',
        'delivery_signature_url' => 'sometimes|url',
        'photo_proof_urls' => 'sometimes|json',
    ]);
    
    // Update order with new status
    // If status === 'delivered', trigger stock sync
    // Record status in history
    // Send appropriate notification
}
```

### Phase 4: Frontend Updates
1. **Order Status Timeline Component**
   - Visual timeline showing all steps
   - Current step highlighted
   - Estimated vs actual times

2. **Delivery Tracking Page**
   - Real-time status updates
   - Map showing delivery route (if available)
   - Driver contact info
   - Estimated arrival time

3. **Owner Notifications**
   - Status change alerts
   - Delivery notifications
   - Failed delivery with retry option

4. **Supplier Delivery Management**
   - Quick status update buttons
   - Bulk delivery confirmations
   - Photo/signature upload for delivery proof

---

## 📱 Frontend Components Needed

### Owner App Pages
1. **Supply Orders List**
   - Filter by status (pending, in_transit, delivered)
   - Color-coded status badges
   - Quick actions (track, confirm delivery)

2. **Order Detail Page**
   - Full timeline visualization
   - Current step details
   - Items and pricing
   - Confirm receipt button

3. **Live Tracking Map** (optional)
   - Shows delivery location
   - Route to delivery point
   - Real-time updates

### Supplier App Pages
1. **Orders to Fulfill**
   - Filter by status
   - Quick confirm/ship buttons
   - Batch operations

2. **Active Deliveries**
   - Orders out for delivery
   - Mark as delivered with photo/signature
   - Handle failed deliveries

---

## 🔔 Notification Triggers

| Event | Recipient | Message |
|-------|-----------|---------|
| Owner places order | Supplier | "New order #{id} from {owner}" |
| Supplier confirms | Owner | "Order confirmed by {supplier}" |
| Payment confirmed | Both | "Payment received for order #{id}" |
| Shipped | Owner | "Your order has shipped" |
| Out for delivery | Owner | "Order arriving today by {driver}" |
| Delivered | Owner | "Order received! Stock updated" |
| Delivery failed | Owner | "Delivery failed. Retry?" |
| Order cancelled | Both | "Order cancelled" |

---

## ⚠️ Important: Stock Update Logic

### Current (Wrong)
```
Order Status Changed to "confirmed"
    ↓
Immediately sync kitchen stock
    ↓
Problem: If delivery fails, stock is still in system
```

### Proposed (Correct)
```
Order Status Changed to "delivered" (with proof)
    ↓
Verify delivery proof (signature/photos)
    ↓
Update owner's inventory
    ↓
Record transaction as complete
    ↓
Email/notification to owner
```

---

## 🧪 Testing Checklist

- [ ] Create supply order (pending)
- [ ] Supplier confirms (confirmed)
- [ ] Process payment (payment_confirmed)
- [ ] Supplier ships (shipped)
- [ ] Mark out for delivery (out_for_delivery)
- [ ] Mark as delivered (delivered)
  - [ ] Verify kitchen stock is updated
  - [ ] Verify supplier available_stock is decremented
  - [ ] Verify transaction is recorded
- [ ] Test delivery failure retry logic
- [ ] Test order cancellation at each stage
- [ ] Test notifications for each status
- [ ] Test permissions (owner vs supplier views)

---

## 📈 Future Enhancements

1. **Payment Integration**
   - GCash, Bank Transfer, COD
   - Automatic payment status updates
   - Payment receipts

2. **Route Optimization**
   - Multi-stop deliveries
   - Optimized routing
   - Delivery scheduling

3. **Analytics Dashboard**
   - Delivery performance metrics
   - Average delivery time by supplier
   - Customer satisfaction ratings

4. **Audit Trail**
   - Complete history of all status changes
   - Who made the change and when
   - Previous values vs new values

5. **Proof of Delivery**
   - GPS coordinates at delivery
   - Photo capture
   - Digital signature
   - OTP verification

---

## 🎯 Summary

The proposed workflow ensures:
✅ Owner sees every step of the process
✅ Stock only updates when delivery is confirmed
✅ Delivery failures can be handled with retries
✅ Complete audit trail for all transactions
✅ Real-time notifications keep both parties informed
✅ Professional delivery tracking experience
