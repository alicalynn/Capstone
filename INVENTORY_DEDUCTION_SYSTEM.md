# Inventory Deduction System - Implementation Guide

## Overview

Your inventory system now has **automatic inventory deduction** when orders are placed! Here's how it works:

### The Complete Flow

```
1. Owner adds inventory items (e.g., pork, tomato)
   ↓
2. Owner creates menu items (e.g., Pancit)
   ↓
3. Owner specifies which inventory items each menu item needs and how much
   ↓
4. Customer places an order
   ↓
5. System checks if enough inventory exists
   ↓
6. If yes: Order is created AND inventory is automatically deducted
   If no: Order is rejected with error message
```

---

## Key Components

### 1. **MenuItemIngredient Model & Table**
Links menu items to inventory items with quantities needed per serving.

**Database Table:** `menu_item_ingredients`
```sql
- menu_item_id: Which menu item (e.g., Pancit)
- inventory_id: Which ingredient (e.g., Pork)
- quantity_needed: How much per serving (e.g., 1.0 kg)
```

### 2. **Updated OrderController**
Now includes inventory validation and deduction logic:
- Checks each ingredient's availability BEFORE creating order
- Rejects order if insufficient stock with detailed error message
- Deducts inventory automatically after order is created

### 3. **MenuItemIngredientController**
API endpoints for karenderia owners to manage menu item ingredients:
- GET `/api/menu-item-ingredients/{menuItemId}` - View ingredients
- POST `/api/menu-item-ingredients` - Add ingredient to menu item
- PUT `/api/menu-item-ingredients/{id}` - Update ingredient quantity
- DELETE `/api/menu-item-ingredients/{id}` - Remove ingredient
- GET `/api/menu-item-ingredients/available-inventory` - List available inventory items

---

## How to Use (For Karenderia Owners)

### Step 1: Add Inventory Items
Endpoint: `POST /api/inventory`
```json
{
  "item_name": "Pork",
  "category": "Meat",
  "unit": "kg",
  "current_stock": 10,
  "minimum_stock": 2,
  "maximum_stock": 20,
  "unit_cost": 200
}
```

### Step 2: Create a Menu Item
Endpoint: `POST /api/menu-items`
```json
{
  "name": "Pancit Palabok",
  "description": "Traditional Filipino noodle dish",
  "price": 150,
  "cost_price": 80,
  "category": "Noodles",
  "preparation_time_minutes": 15
}
```

### Step 3: Link Ingredients to Menu Item
Endpoint: `POST /api/menu-item-ingredients`
```json
{
  "menu_item_id": 1,
  "inventory_id": 5,
  "quantity_needed": 1.0
}
```

**Example Setup for Pancit Palabok:**
```
Ingredient 1: Pork - 1.0 kg per serving
Ingredient 2: Tomato - 0.5 kg per serving
Ingredient 3: Garlic - 0.1 kg per serving
```

---

## How the System Works (Technical Details)

### Order Creation Flow

When a customer places an order for **3 servings of Pancit Palabok**:

1. **Inventory Calculation:**
   - Pork needed: 1.0 kg × 3 servings = **3 kg**
   - Tomato needed: 0.5 kg × 3 servings = **1.5 kg**
   - Garlic needed: 0.1 kg × 3 servings = **0.3 kg**

2. **Stock Validation:**
   - Check Pork inventory: Have 10 kg, Need 3 kg ✓
   - Check Tomato inventory: Have 15 kg, Need 1.5 kg ✓
   - Check Garlic inventory: Have 5 kg, Need 0.3 kg ✓

3. **Order Creation:**
   - Create order record
   - Create order items

4. **Inventory Deduction:**
   - Pork: 10 kg → 7 kg
   - Tomato: 15 kg → 13.5 kg
   - Garlic: 5 kg → 4.7 kg
   - Status updated: If any item drops below minimum_stock, status = 'low_stock'

### Error Handling

If insufficient inventory:
```json
{
  "success": false,
  "message": "Insufficient inventory for order",
  "details": {
    "item": "Pancit Palabok",
    "required_ingredient": "Pork",
    "needed": 3,
    "available": 2.5,
    "unit": "kg"
  }
}
```

---

## Response Examples

### Successful Order with Inventory Deduction
```json
{
  "success": true,
  "message": "Order created successfully and inventory deducted",
  "data": {
    "id": 1001,
    "order_number": "ORD-2026-04-29-001",
    "inventory_deducted": true
  }
}
```

### Failed Order (Insufficient Inventory)
```json
{
  "success": false,
  "message": "Insufficient inventory for order",
  "details": {
    "item": "Pancit Palabok",
    "required_ingredient": "Pork",
    "needed": 3,
    "available": 1.5,
    "unit": "kg"
  }
}
```

---

## Testing the Feature

### Quick Test Script
Run the provided test script:
```bash
cd laravel-backend
php test_inventory_deduction.php
```

This will:
1. Create test inventory items (Pork, Tomato, Garlic)
2. Create a test menu item (Pancit Palabok)
3. Link ingredients to the menu item
4. Simulate an order
5. Verify inventory is deducted correctly
6. Display before/after inventory levels

---

## Key Features

✅ **Automatic Deduction** - No manual adjustment needed
✅ **Stock Validation** - Prevents overselling
✅ **Error Messages** - Clear feedback on what's missing
✅ **Low Stock Alerts** - Automatic status updates
✅ **Multiple Ingredients** - Support for complex recipes
✅ **Precise Tracking** - Decimal quantities (1.5 kg, 0.3 kg, etc.)
✅ **Audit Trail** - All deductions recorded with order reference

---

## Database Changes

### New Table: menu_item_ingredients
```sql
CREATE TABLE menu_item_ingredients (
    id BIGINT PRIMARY KEY,
    menu_item_id BIGINT (FK: menu_items),
    inventory_id BIGINT (FK: inventory),
    quantity_needed DECIMAL(8,3),
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(menu_item_id, inventory_id),
    INDEX(menu_item_id),
    INDEX(inventory_id)
);
```

### Updated Models:
- `MenuItem` - Added relationship: `ingredients()` → HasMany MenuItemIngredient
- `MenuItemIngredient` - New model with relationships to MenuItem and Inventory
- `OrderController` - Updated store() method with inventory validation and deduction

---

## For Capstone Defense

### What to Demonstrate:

1. **Setup Phase:**
   - Show owner adding inventory items (pork, tomato, etc.)
   - Show creating menu items with prices

2. **Configuration Phase:**
   - Show linking ingredients to menu items
   - Show specifying quantities needed per serving

3. **Order Phase:**
   - Have customer place an order
   - Show order gets created ✓
   - **Show inventory automatically deducts** ✓

4. **Validation Phase:**
   - Try to place an order with insufficient inventory
   - Show error message rejecting the order

---

## Code References

### Key Files:
- Migration: `database/migrations/2026_04_29_create_menu_item_ingredients_table.php`
- Model: `app/Models/MenuItemIngredient.php`
- Controller: `app/Http/Controllers/MenuItemIngredientController.php`
- Updated: `app/Http/Controllers/OrderController.php` (store method)
- Routes: `routes/api.php` (menu-item-ingredients prefix)
- Test: `test_inventory_deduction.php`

---

## Next Steps (Optional Enhancements)

1. **Daily Menu Integration** - Automatically deduct for daily menu items
2. **Restock Automation** - Auto-notify when low stock threshold reached
3. **Bulk Deduction** - Support for batch orders
4. **Inventory History** - Detailed logs of all deductions
5. **Forecasting** - Predict future inventory needs based on sales

---

## Troubleshooting

### Orders not being rejected when inventory is low?
- Run migrations: `php artisan migrate`
- Check that MenuItemIngredient table exists
- Verify menu items have ingredients configured

### Inventory not deducting?
- Ensure menu item has linked ingredients
- Check that inventory_id exists and belongs to same karenderia
- Verify InventoryService::deductStock() is being called

### Getting "Insufficient inventory" error?
- This is expected! It means validation is working
- Add more inventory items or reduce order quantity

---

For questions or issues during demo, reference this guide!
