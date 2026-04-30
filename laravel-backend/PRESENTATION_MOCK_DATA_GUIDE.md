# KaPlato Presentation Mock Data Guide

This guide maps seeded realistic data to your Slide 4 script flow.

## Seed command

Run from `Capstone/laravel-backend`:

```bash
php create_realistic_presentation_data.php
```

The script is idempotent and safe to rerun.

## Demo accounts

- Primary owner: `mikaela.santos@sukimeals.ph` / `owner1234`
- Customer (peanut allergy demo): `paolo.dizon@citymail.ph` / `customer1234`
- Supplier: `sales@metrofresh.ph` / `supplier1234`
- Supplier: `orders@greenbasket.ph` / `supplier1234`

## What is seeded for screenshots

### 1) User searches meals (allergen, calories, budget, distance)

- Multiple customers have realistic allergen profiles (peanuts, shellfish, eggs, soy, dairy, fish, gluten).
- Nearby karenderias in Makati are seeded with valid lat/long.
- Menu items include both risky and safer alternatives:
  - Risky: `Beef Kare-Kare sa Puso ng Saging` (contains peanuts, high calories)
  - Safer alternatives: `Chicken Tinola ng Legazpi`, `Ginisang Monggo with Tokwa`, `Tofu Veggie Budget Bowl`
- Budget-friendly and premium prices are mixed for real filter outcomes.

### 2) System analyzes ingredients (allergen detection + calories)

- Menu entries include `ingredients`, `allergens`, and `calories` values.
- You can demo warning behavior with peanut-allergy user selecting `Beef Kare-Kare sa Puso ng Saging`.
- You can demo safer recommendations with `Chicken Tinola ng Legazpi` and `Ginisang Monggo with Tokwa`.

### 3) Owners manage menus + suppliers handle orders

- Owner-side seeded data:
  - active karenderia
  - realistic menu items with ratings/order counts
  - 18 months of historical customer orders for analytics
  - current pending/preparing POS orders
  - low-stock and out-of-stock items to justify supplier ordering
- Supplier workflow seeded data:
  - supplier marketplace listings
  - Suki relationships
  - multi-month supply orders in `pending`, `confirmed`, `delivered`, and occasional `cancelled` states

## Fast API checks

- Menu list for owner karenderia:
  - `GET /api/menu-items/search?karenderia=<owner_karenderia_id>`
- Nearby karenderias:
  - `GET /api/karenderias/nearby?latitude=14.5560&longitude=121.0260&radius=3000`
- Owner supply orders (authenticated as owner):
  - `GET /api/supply/orders/owner`
- Supplier supply orders (authenticated as supplier):
  - `GET /api/supply/orders/supplier`
