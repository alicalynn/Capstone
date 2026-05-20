# Delivery Workflow System - Implementation Guide

## 📦 Deliverables

This package includes the complete enhanced delivery workflow system for managing supplier-owner orders with full status tracking.

---

## 🔧 Installation Steps

### Step 1: Run Database Migration

```bash
cd laravel-backend

# Run the new migration
php artisan migrate --path=database/migrations/2026_05_20_000001_enhance_supply_orders_with_delivery_tracking.php

# Or run all pending migrations
php artisan migrate
```

**What this does:**
- Adds payment status tracking fields
- Adds delivery tracking fields (method, address, coordinates)
- Adds delivery proof fields (signature, photos)
- Adds status timestamps for each stage
- Adds retry logic for failed deliveries
- Creates status history JSON column for audit trail

### Step 2: Verify Model Updates

The `SupplyOrder` model has been updated with:
- New fillable attributes for all tracking fields
- Helper methods: `getStatusTimeline()`, `getNextPossibleStatuses()`, `canBeRetried()`, `isTerminal()`
- Status history recording via `recordStatusChange()`
- Cast definitions for JSON columns

### Step 3: Verify Controller Updates

The `SupplierWorkflowController` has been updated with:
- Enhanced `updateOrderStatus()` method supporting all new statuses
- New `getOrderDetail()` endpoint for detailed order tracking
- Proper permission checks for both supplier and owner roles
- Stock sync ONLY on delivery (not on confirmation)
- Comprehensive error handling and validation

### Step 4: Verify API Routes

New/Updated routes in `routes/api.php`:
```
GET    /api/supply/orders/{orderId}                    # Get detailed order with timeline
PATCH  /api/supply/orders/{orderId}/status             # Update order status
```

---

## 📊 New Status Flow

### Complete Status Diagram

```
┌─────────┐
│ pending │  Owner places order
└────┬────┘
     │
     ↓
┌───────────┐
│confirmed  │  Supplier confirms they'll fulfill
└────┬──────┘
     │
     ↓
┌─────────────────┐
│payment_confirmed│  Payment processed
└────┬────────────┘
     │
     ↓
┌──────────┐
│ preparing│  Supplier picking/packing items
└────┬─────┘
     │
     ↓
┌────────┐
│shipped │  Order shipped from supplier
└────┬───┘
     │
     ├─→ in_transit      [optional intermediate step]
     │
     ↓
┌──────────────────┐
│out_for_delivery  │  With driver/courier info
└────┬─────────────┘
     │
     ├────────────────┐
     ↓                ↓
 ┌────────┐    ┌───────────────┐
 │delivered│    │delivery_failed│  ← Can retry if retries < max
 └────────┘    └───┬───────────┘
     ↑              │
     │   retry      ↓
     │         ┌───────────────┐
     │         │out_for_delivery│  Try again
     └─────────┘                └──→ delivered
     
At any stage: → cancelled (restore supplier stock)
```

---

## 🧪 API Usage Examples

### 1. Place a Supply Order

```bash
POST /api/supply/orders
Content-Type: application/json
Authorization: Bearer {token}

{
  "items": [
    {
      "supplier_inventory_item_id": 1,
      "quantity": 50
    }
  ],
  "notes": "Urgent delivery needed",
  "delivery_date": "2026-05-21"
}

Response:
{
  "message": "Supply order placed successfully",
  "data": {
    "id": 1,
    "status": "pending",
    "total_amount": 500.00,
    "created_at": "2026-05-20T08:00:00Z"
  }
}
```

### 2. Get Order Detail with Timeline

```bash
GET /api/supply/orders/1
Authorization: Bearer {token}

Response:
{
  "data": {
    "order": {
      "id": 1,
      "status": "out_for_delivery",
      "total_amount": 500.00,
      "supplier": {
        "id": 2,
        "name": "ABC Suppliers",
        "email": "abc@suppliers.com"
      },
      "karenderia": {
        "id": 1,
        "business_name": "Alica's Kitchen",
        "address": "123 Main St, Bacolod"
      },
      "items": [
        {
          "id": 1,
          "quantity": "50.000",
          "unit_price": "10.00",
          "line_total": "500.00",
          "supplier_item": {
            "item_name": "Tomatoes",
            "unit": "kg"
          }
        }
      ],
      "payment_status": "confirmed",
      "payment_method": "gcash",
      "delivery_method": "delivery",
      "delivery_address": "123 Main St, Bacolod",
      "delivered_by_name": "John Driver",
      "confirmed_at": "2026-05-20T08:15:00Z",
      "shipped_at": "2026-05-20T10:00:00Z",
      "out_for_delivery_at": "2026-05-20T14:30:00Z",
      "delivered_at": null
    },
    "timeline": [
      {
        "status": "pending",
        "label": "Order Placed",
        "timestamp": "2026-05-20T08:00:00Z",
        "completed": true,
        "current": false
      },
      {
        "status": "confirmed",
        "label": "Supplier Confirmed",
        "timestamp": "2026-05-20T08:15:00Z",
        "completed": true,
        "current": false
      },
      {
        "status": "payment_confirmed",
        "label": "Payment Confirmed",
        "timestamp": "2026-05-20T08:30:00Z",
        "completed": true,
        "current": false
      },
      {
        "status": "preparing",
        "label": "Being Prepared",
        "timestamp": null,
        "completed": false,
        "current": false
      },
      {
        "status": "shipped",
        "label": "Shipped",
        "timestamp": "2026-05-20T10:00:00Z",
        "completed": true,
        "current": false
      },
      {
        "status": "in_transit",
        "label": "In Transit",
        "timestamp": null,
        "completed": false,
        "current": false
      },
      {
        "status": "out_for_delivery",
        "label": "Out for Delivery",
        "timestamp": "2026-05-20T14:30:00Z",
        "completed": false,
        "current": true
      },
      {
        "status": "delivered",
        "label": "Delivered",
        "timestamp": null,
        "completed": false,
        "current": false
      }
    ],
    "next_possible_statuses": ["delivered", "delivery_failed"],
    "is_terminal": false,
    "can_retry": false
  }
}
```

### 3. Update Order Status (Supplier confirms)

```bash
PATCH /api/supply/orders/1/status
Content-Type: application/json
Authorization: Bearer {supplier_token}

{
  "status": "confirmed"
}

Response:
{
  "message": "Order status updated successfully",
  "data": {
    "order": { ... updated order ... },
    "timeline": [ ... updated timeline ... ],
    "next_possible_statuses": ["payment_confirmed", "cancelled"]
  }
}
```

### 4. Mark as Paid

```bash
PATCH /api/supply/orders/1/status
Content-Type: application/json
Authorization: Bearer {supplier_token}

{
  "status": "payment_confirmed",
  "payment_method": "gcash",
  "payment_reference": "GCash-12345"
}
```

### 5. Mark as Shipped

```bash
PATCH /api/supply/orders/1/status
Content-Type: application/json
Authorization: Bearer {supplier_token}

{
  "status": "shipped"
}
```

### 6. Mark as Out for Delivery

```bash
PATCH /api/supply/orders/1/status
Content-Type: application/json
Authorization: Bearer {supplier_token}

{
  "status": "out_for_delivery",
  "delivery_method": "delivery",
  "delivery_address": "123 Main St, Bacolod",
  "delivered_by_name": "John Driver"
}
```

### 7. Mark as Delivered (Stock Updates Here!)

```bash
PATCH /api/supply/orders/1/status
Content-Type: application/json
Authorization: Bearer {supplier_token}

{
  "status": "delivered",
  "delivery_notes": "Delivered to kitchen",
  "delivery_signature_url": "https://example.com/signature.jpg",
  "photo_proof_urls": "[\"https://example.com/photo1.jpg\", \"https://example.com/photo2.jpg\"]"
}

Response:
{
  "message": "Order status updated successfully",
  "data": {
    "order": {
      "status": "delivered",
      "delivered_at": "2026-05-20T16:00:00Z",
      "delivery_signature_url": "https://example.com/signature.jpg"
    },
    "timeline": [ ... all complete ... ]
  }
}

NOTE: At this point, the owner's kitchen inventory is automatically updated!
```

### 8. Handle Delivery Failure (with retry)

```bash
PATCH /api/supply/orders/1/status
Content-Type: application/json
Authorization: Bearer {supplier_token}

{
  "status": "delivery_failed",
  "failed_reason": "Owner not home, building locked"
}

Response:
{
  "message": "Order status updated successfully",
  "data": {
    "order": {
      "status": "delivery_failed",
      "retry_count": 1,
      "max_retries": 3,
      "failed_reason": "Owner not home, building locked"
    },
    "next_possible_statuses": ["out_for_delivery"]  # Can retry
  }
}
```

### 9. Cancel Order (Owner)

```bash
PATCH /api/supply/orders/1/status
Content-Type: application/json
Authorization: Bearer {owner_token}

{
  "status": "cancelled"
}

Response:
{
  "message": "Order status updated successfully",
  "data": {
    "order": {
      "status": "cancelled"
    },
    "stock_actions": {
      "supplier_stock_restored": true,
      "owner_inventory_updated": false
    }
  }
}
```

---

## 💻 Frontend Implementation Examples

### React Component - Order Status Timeline

```typescript
// OrderTracker.tsx
import React from 'react';

interface TimelineItem {
  status: string;
  label: string;
  timestamp: string | null;
  completed: boolean;
  current: boolean;
}

const OrderTracker: React.FC<{ timeline: TimelineItem[] }> = ({ timeline }) => {
  return (
    <div className="order-tracker">
      <div className="timeline">
        {timeline.map((item, index) => (
          <div
            key={item.status}
            className={`timeline-item ${item.completed ? 'completed' : ''} ${
              item.current ? 'current' : ''
            }`}
          >
            <div className="timeline-marker">
              {item.completed && <span>✓</span>}
              {item.current && <span>●</span>}
              {!item.completed && !item.current && <span>○</span>}
            </div>
            <div className="timeline-content">
              <h4>{item.label}</h4>
              {item.timestamp && (
                <p>{new Date(item.timestamp).toLocaleString()}</p>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default OrderTracker;
```

### Angular Component - Order Status Buttons

```typescript
// order-actions.component.ts
import { Component, Input } from '@angular/core';
import { SupplyService } from './supply.service';

@Component({
  selector: 'app-order-actions',
  template: `
    <ion-card *ngIf="nextStatuses.length > 0">
      <ion-card-header>
        <ion-card-title>Update Order Status</ion-card-title>
      </ion-card-header>
      <ion-card-content>
        <div *ngFor="let status of nextStatuses" class="action-button">
          <ion-button
            (click)="updateStatus(status)"
            [disabled]="isLoading"
            expand="block"
          >
            {{ getStatusLabel(status) }}
          </ion-button>
        </div>
      </ion-card-content>
    </ion-card>
  `,
})
export class OrderActionsComponent {
  @Input() orderId!: number;
  @Input() nextStatuses: string[] = [];

  isLoading = false;

  constructor(private supplyService: SupplyService) {}

  updateStatus(status: string): void {
    this.isLoading = true;
    this.supplyService.updateOrderStatus(this.orderId, { status }).subscribe(
      () => {
        this.isLoading = false;
        // Refresh order data
      },
      (error) => {
        this.isLoading = false;
        console.error('Error updating status:', error);
      }
    );
  }

  getStatusLabel(status: string): string {
    const labels: { [key: string]: string } = {
      confirmed: 'Confirm Order',
      payment_confirmed: 'Mark Payment Received',
      preparing: 'Mark as Preparing',
      shipped: 'Mark as Shipped',
      in_transit: 'Mark in Transit',
      out_for_delivery: 'Out for Delivery',
      delivered: 'Mark Delivered',
      delivery_failed: 'Delivery Failed',
      cancelled: 'Cancel Order',
    };
    return labels[status] || status;
  }
}
```

### Vue Component - Order Status Modal

```vue
<template>
  <div class="order-status-modal">
    <h2>{{ order.status | uppercase }}</h2>

    <!-- Timeline Visualization -->
    <div class="timeline-container">
      <div
        v-for="item in timeline"
        :key="item.status"
        :class="[
          'timeline-step',
          { completed: item.completed },
          { current: item.current },
        ]"
      >
        <div class="step-marker">
          <span v-if="item.completed">✓</span>
          <span v-else-if="item.current">→</span>
          <span v-else>•</span>
        </div>
        <div class="step-label">
          {{ item.label }}
          <small v-if="item.timestamp">{{ formatTime(item.timestamp) }}</small>
        </div>
      </div>
    </div>

    <!-- Status Update Buttons -->
    <div v-if="nextStatuses.length > 0" class="action-buttons">
      <button
        v-for="status in nextStatuses"
        :key="status"
        @click="selectStatus(status)"
        class="btn-status"
      >
        {{ getLabel(status) }}
      </button>
    </div>

    <!-- Status Update Form (if form needed) -->
    <form v-if="selectedStatus && needsForm(selectedStatus)" @submit.prevent="submitStatus">
      <input v-if="selectedStatus === 'delivered'" v-model="form.delivery_notes" placeholder="Delivery notes" />
      <input v-if="selectedStatus === 'delivery_failed'" v-model="form.failed_reason" placeholder="Failure reason" />
      <button type="submit">Confirm {{ getLabel(selectedStatus) }}</button>
    </form>
  </div>
</template>

<script>
export default {
  data() {
    return {
      order: null,
      timeline: [],
      nextStatuses: [],
      selectedStatus: null,
      form: {},
    };
  },
  methods: {
    formatTime(timestamp) {
      return new Date(timestamp).toLocaleString();
    },
    getLabel(status) {
      const labels = {
        confirmed: 'Confirm Order',
        payment_confirmed: 'Mark as Paid',
        // ... other labels
      };
      return labels[status] || status;
    },
    needsForm(status) {
      return ['delivered', 'delivery_failed'].includes(status);
    },
    selectStatus(status) {
      this.selectedStatus = status;
      this.form = {};
    },
    async submitStatus() {
      const data = {
        status: this.selectedStatus,
        ...this.form,
      };
      // Make API call to update status
    },
  },
};
</script>
```

---

## 🧪 Testing Checklist

### Manual Testing Workflow

1. **Create Order (Pending)**
   - [ ] Owner browses marketplace
   - [ ] Selects items from supplier
   - [ ] Places order
   - [ ] Status shows as "pending"
   - [ ] Stock reserved on supplier side

2. **Supplier Confirms**
   - [ ] Supplier sees order in their dashboard
   - [ ] Supplier clicks "Confirm Order"
   - [ ] Status changes to "confirmed"
   - [ ] Owner receives notification
   - [ ] Still no stock update in owner's inventory

3. **Payment Processing**
   - [ ] Supplier marks payment as received
   - [ ] Status changes to "payment_confirmed"
   - [ ] Payment date recorded
   - [ ] Still no stock update in owner's inventory

4. **Preparing & Shipping**
   - [ ] Supplier marks order as "preparing"
   - [ ] Supplier marks order as "shipped"
   - [ ] Owner gets notification
   - [ ] Still no stock update

5. **Out for Delivery**
   - [ ] Supplier marks "out for delivery"
   - [ ] Supplier enters driver name
   - [ ] Owner gets notification with driver info
   - [ ] Still no stock update

6. **Delivery Complete**
   - [ ] Supplier marks "delivered"
   - [ ] Supplier uploads delivery proof (signature/photos)
   - [ ] ✅ **NOW owner's inventory is updated!**
   - [ ] Owner sees items in their kitchen stock
   - [ ] Owner gets confirmation notification

7. **Delivery Failure Scenario**
   - [ ] Supplier marks "delivery failed"
   - [ ] Provides reason
   - [ ] No stock update
   - [ ] Supplier can retry (max 3 times)
   - [ ] On retry success, stock updates

8. **Cancellation**
   - [ ] Owner cancels order at any stage
   - [ ] Supplier stock is restored
   - [ ] If delivered, owner stock is rolled back
   - [ ] Both receive notifications

### API Testing with Postman/cURL

```bash
# 1. Get order with timeline
curl -X GET http://localhost:8000/api/supply/orders/1 \
  -H "Authorization: Bearer {token}"

# 2. Update to confirmed
curl -X PATCH http://localhost:8000/api/supply/orders/1/status \
  -H "Authorization: Bearer {supplier_token}" \
  -H "Content-Type: application/json" \
  -d '{"status": "confirmed"}'

# 3. Verify timeline updated
curl -X GET http://localhost:8000/api/supply/orders/1 \
  -H "Authorization: Bearer {token}"
```

---

## 🚀 Key Features Implemented

✅ **Complete Status Tracking**
- 10 different order statuses with clear transitions
- Timestamp for each status change
- Visual timeline for users

✅ **Delayed Stock Update**
- Stock only updates when delivery confirmed
- Prevents inventory discrepancies
- Proper rollback on cancellation

✅ **Delivery Proof**
- Signature capture
- Photo evidence
- Driver information
- Delivery notes

✅ **Retry Logic**
- Failed deliveries can be retried (max 3)
- Track retry count
- Clear failure reasons

✅ **Comprehensive Audit Trail**
- Status history JSON
- All timestamps recorded
- Who made changes and when
- Previous status recorded

✅ **Permission Controls**
- Supplier can update forward flow
- Owner can only cancel
- Clear authorization checks

---

## ⚠️ Important Notes

### Stock Sync Trigger
**BEFORE:** Stock updated on "confirmed" status (WRONG)
**AFTER:** Stock updated only on "delivered" status (CORRECT) ✅

### Status Transitions
Not all statuses are mandatory. You can skip steps:
- pending → confirmed → payment_confirmed → shipped → **delivered** (minimal)
- pending → confirmed → payment_confirmed → **preparing** → shipped → out_for_delivery → **delivered** (detailed)

### Permissions
- **Supplier:** Can advance status through workflow
- **Owner:** Can ONLY cancel (refund/prevent stock update)

---

## 📝 Summary

This implementation provides:

1. **Database schema** with comprehensive tracking fields
2. **Model enhancements** with helper methods for workflow logic
3. **Controller updates** with proper status transitions
4. **API endpoints** for status tracking and updates
5. **Frontend examples** for multiple frameworks
6. **Testing checklist** for validation

The key improvement: **Stock only updates when delivery is confirmed**, ensuring accurate inventory management and preventing issues with failed deliveries.

---

## 🆘 Troubleshooting

**Issue:** "Invalid status transition" error
- **Solution:** Check the allowed status transitions in `SupplyOrder::getNextPossibleStatuses()`

**Issue:** Stock not updating on delivery
- **Solution:** Ensure status is exactly "delivered" and all required fields are populated

**Issue:** Migration fails
- **Solution:** Check database compatibility (MySQL 5.7+ required for JSON columns)

**Issue:** Timeline shows wrong timestamps
- **Solution:** Verify timestamps are being set for each status change

---

## 📞 Support

For questions or issues:
1. Check DELIVERY_WORKFLOW_DESIGN.md for conceptual overview
2. Review API usage examples above
3. Check Laravel logs: `storage/logs/laravel.log`
4. Enable DEBUG in .env for detailed error messages
