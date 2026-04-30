<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Allergen;
use App\Models\DailyMenu;
use App\Models\Inventory;
use App\Models\Karenderia;
use App\Models\KarenderiaSupplierSuki;
use App\Models\MenuItem;
use App\Models\SupplierInventoryItem;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

function line(string $message = ''): void
{
    echo $message . PHP_EOL;
}

line('=====================================================');
line('KaPlato Presentation Demo Data Seeder');
line('=====================================================');

DB::beginTransaction();

try {
    $now = now();

    // 1) Demo users for customer, owners, and suppliers.
    $demoUsers = [
        'customer' => User::updateOrCreate(
            ['email' => 'demo.customer@kaplato.com'],
            [
                'name' => 'Demo Customer',
                'password' => Hash::make('demo1234'),
                'role' => 'customer',
                'verified' => true,
                'application_status' => 'approved',
                'email_verified_at' => $now,
                'phone_number' => '09171234567',
                'address' => 'Legazpi Village, Makati City',
                'allergies' => ['peanuts'],
                'dietary_restrictions' => ['low_sodium'],
                'activity_level' => 'moderate',
                'fitness_goal' => 'weight_loss',
                'height' => 165,
                'weight' => 63,
            ]
        ),
        'owner_a' => User::updateOrCreate(
            ['email' => 'demo.owner@kaplato.com'],
            [
                'name' => 'Demo Owner One',
                'password' => Hash::make('owner1234'),
                'role' => 'karenderia_owner',
                'verified' => true,
                'application_status' => 'approved',
                'email_verified_at' => $now,
                'phone_number' => '09181112222',
                'address' => 'Makati City',
            ]
        ),
        'owner_b' => User::updateOrCreate(
            ['email' => 'demo.owner2@kaplato.com'],
            [
                'name' => 'Demo Owner Two',
                'password' => Hash::make('owner1234'),
                'role' => 'karenderia_owner',
                'verified' => true,
                'application_status' => 'approved',
                'email_verified_at' => $now,
                'phone_number' => '09183334444',
                'address' => 'Poblacion, Makati City',
            ]
        ),
        'supplier_a' => User::updateOrCreate(
            ['email' => 'demo.supplier1@kaplato.com'],
            [
                'name' => 'Metro Fresh Supply',
                'password' => Hash::make('supplier1234'),
                'role' => 'supplier',
                'verified' => true,
                'application_status' => 'approved',
                'email_verified_at' => $now,
                'phone_number' => '09220001111',
                'address' => 'Pasig Public Market, Pasig City',
            ]
        ),
        'supplier_b' => User::updateOrCreate(
            ['email' => 'demo.supplier2@kaplato.com'],
            [
                'name' => 'Budget Gulay Traders',
                'password' => Hash::make('supplier1234'),
                'role' => 'supplier',
                'verified' => true,
                'application_status' => 'approved',
                'email_verified_at' => $now,
                'phone_number' => '09223335555',
                'address' => 'Guadalupe Market, Makati City',
            ]
        ),
    ];

    // 2) Explicit allergen profile for warning/safer alternatives flow.
    Allergen::updateOrCreate(
        ['user_id' => $demoUsers['customer']->id, 'name' => 'peanuts'],
        [
            'category' => 'nuts',
            'severity' => 'severe',
            'notes' => 'Presentation demo profile: severe peanut allergy',
        ]
    );

    Allergen::updateOrCreate(
        ['user_id' => $demoUsers['customer']->id, 'name' => 'shellfish'],
        [
            'category' => 'seafood',
            'severity' => 'moderate',
            'notes' => 'Optional secondary allergen for filtering demo',
        ]
    );

    // 3) Nearby karenderias for distance + safer alternatives.
    $karenderiaA = Karenderia::updateOrCreate(
        ['owner_id' => $demoUsers['owner_a']->id],
        [
            'name' => 'Demo Karenderia One',
            'business_name' => 'Suki Meals Makati',
            'description' => 'Home-style Filipino meals with allergen-labeled menu options.',
            'address' => '120 Dela Rosa St, Makati City',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'phone' => '09181112222',
            'email' => 'owner.demo@kaplato.com',
            'business_email' => 'owner.demo@kaplato.com',
            'latitude' => 14.5547,
            'longitude' => 121.0244,
            'opening_time' => '06:00:00',
            'closing_time' => '21:00:00',
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'status' => 'active',
            'approved_at' => $now,
            'delivery_fee' => 35,
            'delivery_time_minutes' => 25,
            'accepts_cash' => true,
            'accepts_online_payment' => true,
            'average_rating' => 4.7,
            'total_reviews' => 143,
        ]
    );

    $karenderiaB = Karenderia::updateOrCreate(
        ['owner_id' => $demoUsers['owner_b']->id],
        [
            'name' => 'Demo Karenderia Two',
            'business_name' => 'Healthy Turo-Turo Hub',
            'description' => 'Budget-friendly and lower-calorie dishes for office lunch crowds.',
            'address' => '248 Bautista St, Makati City',
            'city' => 'Makati',
            'province' => 'Metro Manila',
            'phone' => '09183334444',
            'email' => 'owner.demo2@kaplato.com',
            'business_email' => 'owner.demo2@kaplato.com',
            'latitude' => 14.5622,
            'longitude' => 121.0321,
            'opening_time' => '07:00:00',
            'closing_time' => '20:00:00',
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'status' => 'active',
            'approved_at' => $now,
            'delivery_fee' => 25,
            'delivery_time_minutes' => 20,
            'accepts_cash' => true,
            'accepts_online_payment' => true,
            'average_rating' => 4.5,
            'total_reviews' => 98,
        ]
    );

    // 4) Menu data for search filters: allergen, calories, budget, and alternatives.
    $menuItems = [];

    $menuItems['kare_kare'] = MenuItem::updateOrCreate(
        ['karenderia_id' => $karenderiaA->id, 'name' => 'Beef Kare-Kare'],
        [
            'description' => 'Rich peanut stew with oxtail and vegetables.',
            'price' => 195.00,
            'cost_price' => 120.00,
            'category' => 'main_course',
            'is_available' => true,
            'is_featured' => true,
            'preparation_time_minutes' => 25,
            'calories' => 640,
            'ingredients' => ['oxtail', 'peanut sauce', 'eggplant', 'banana blossom', 'string beans'],
            'allergens' => ['peanuts'],
            'dietary_info' => 'Contains peanuts',
            'spice_level' => 1,
            'serving_size' => 1,
            'average_rating' => 4.6,
            'total_reviews' => 74,
            'total_orders' => 189,
        ]
    );

    $menuItems['monggo'] = MenuItem::updateOrCreate(
        ['karenderia_id' => $karenderiaA->id, 'name' => 'Ginisang Monggo with Malunggay'],
        [
            'description' => 'Protein-rich mung bean stew with leafy greens.',
            'price' => 89.00,
            'cost_price' => 45.00,
            'category' => 'main_course',
            'is_available' => true,
            'is_featured' => false,
            'preparation_time_minutes' => 15,
            'calories' => 280,
            'ingredients' => ['mung beans', 'malunggay', 'garlic', 'onion', 'tomato'],
            'allergens' => [],
            'dietary_info' => 'Allergen-safe option in this menu',
            'spice_level' => 1,
            'serving_size' => 1,
            'average_rating' => 4.4,
            'total_reviews' => 52,
            'total_orders' => 130,
        ]
    );

    $menuItems['tinola'] = MenuItem::updateOrCreate(
        ['karenderia_id' => $karenderiaA->id, 'name' => 'Chicken Tinola'],
        [
            'description' => 'Light ginger chicken soup with green papaya.',
            'price' => 110.00,
            'cost_price' => 60.00,
            'category' => 'main_course',
            'is_available' => true,
            'is_featured' => true,
            'preparation_time_minutes' => 18,
            'calories' => 320,
            'ingredients' => ['chicken', 'green papaya', 'ginger', 'malunggay', 'fish sauce'],
            'allergens' => [],
            'dietary_info' => 'Lower calorie alternative',
            'spice_level' => 1,
            'serving_size' => 1,
            'average_rating' => 4.7,
            'total_reviews' => 81,
            'total_orders' => 203,
        ]
    );

    $menuItems['palabok'] = MenuItem::updateOrCreate(
        ['karenderia_id' => $karenderiaB->id, 'name' => 'Pancit Palabok'],
        [
            'description' => 'Rice noodles with shrimp sauce and egg.',
            'price' => 115.00,
            'cost_price' => 62.00,
            'category' => 'main_course',
            'is_available' => true,
            'is_featured' => false,
            'preparation_time_minutes' => 16,
            'calories' => 410,
            'ingredients' => ['rice noodles', 'shrimp', 'egg', 'garlic', 'annatto'],
            'allergens' => ['shellfish', 'eggs'],
            'dietary_info' => 'Contains shellfish',
            'spice_level' => 1,
            'serving_size' => 1,
            'average_rating' => 4.3,
            'total_reviews' => 46,
            'total_orders' => 101,
        ]
    );

    $menuItems['tofu_bowl'] = MenuItem::updateOrCreate(
        ['karenderia_id' => $karenderiaB->id, 'name' => 'Tofu Veggie Bowl'],
        [
            'description' => 'Budget meal with tofu, pechay, and brown rice.',
            'price' => 95.00,
            'cost_price' => 48.00,
            'category' => 'main_course',
            'is_available' => true,
            'is_featured' => true,
            'preparation_time_minutes' => 12,
            'calories' => 295,
            'ingredients' => ['tofu', 'pechay', 'brown rice', 'garlic', 'soy sauce'],
            'allergens' => ['soy'],
            'dietary_info' => 'Low calorie, budget friendly',
            'spice_level' => 1,
            'serving_size' => 1,
            'average_rating' => 4.5,
            'total_reviews' => 63,
            'total_orders' => 154,
        ]
    );

    $menuItems['ensalada'] = MenuItem::updateOrCreate(
        ['karenderia_id' => $karenderiaB->id, 'name' => 'Ensaladang Talong Meal'],
        [
            'description' => 'Smoky eggplant salad with grilled fish and rice.',
            'price' => 99.00,
            'cost_price' => 52.00,
            'category' => 'main_course',
            'is_available' => true,
            'is_featured' => false,
            'preparation_time_minutes' => 14,
            'calories' => 260,
            'ingredients' => ['eggplant', 'tomato', 'onion', 'tilapia', 'rice'],
            'allergens' => ['fish'],
            'dietary_info' => 'Lean protein option',
            'spice_level' => 1,
            'serving_size' => 1,
            'average_rating' => 4.2,
            'total_reviews' => 38,
            'total_orders' => 90,
        ]
    );

    // 5) Daily menu availability for customer browse screens.
    $today = now()->toDateString();
    $dailyRows = [
        [$karenderiaA->id, $menuItems['kare_kare']->id, 'lunch', 20, null, null, 'Contains peanuts'],
        [$karenderiaA->id, $menuItems['monggo']->id, 'lunch', 35, null, null, 'Safe alternative for peanut allergy'],
        [$karenderiaA->id, $menuItems['tinola']->id, 'dinner', 30, null, null, 'Low calorie pick'],
        [$karenderiaB->id, $menuItems['tofu_bowl']->id, 'lunch', 28, null, null, 'Budget + low calorie option'],
        [$karenderiaB->id, $menuItems['ensalada']->id, 'dinner', 18, null, null, 'Fresh catch special'],
    ];

    foreach ($dailyRows as [$kId, $mId, $mealType, $qty, $inventoryId, $ingredientQty, $notes]) {
        DailyMenu::updateOrCreate(
            [
                'karenderia_id' => $kId,
                'menu_item_id' => $mId,
                'date' => $today,
                'meal_type' => $mealType,
            ],
            [
                'quantity' => $qty,
                'original_quantity' => $qty,
                'inventory_id' => $inventoryId,
                'ingredient_quantity' => $ingredientQty,
                'is_available' => true,
                'special_price' => null,
                'notes' => $notes,
            ]
        );
    }

    // 6) Owner inventory snapshot (for low stock + reorder story).
    Inventory::updateOrCreate(
        ['karenderia_id' => $karenderiaA->id, 'item_name' => 'Chicken (kg)'],
        [
            'description' => 'Fresh dressed chicken',
            'category' => 'meat',
            'unit' => 'kg',
            'current_stock' => 18,
            'minimum_stock' => 10,
            'maximum_stock' => 40,
            'unit_cost' => 210,
            'total_value' => 3780,
            'supplier' => 'Metro Fresh Supply',
            'last_restocked' => now()->subDays(2)->toDateString(),
            'status' => 'available',
        ]
    );

    Inventory::updateOrCreate(
        ['karenderia_id' => $karenderiaA->id, 'item_name' => 'Malunggay (bunch)'],
        [
            'description' => 'Fresh malunggay leaves',
            'category' => 'vegetables',
            'unit' => 'bunch',
            'current_stock' => 4,
            'minimum_stock' => 8,
            'maximum_stock' => 30,
            'unit_cost' => 18,
            'total_value' => 72,
            'supplier' => 'Budget Gulay Traders',
            'last_restocked' => now()->subDays(4)->toDateString(),
            'status' => 'low_stock',
        ]
    );

    // 7) Supplier marketplace listings.
    $listings = [];

    $listings['chicken'] = SupplierInventoryItem::updateOrCreate(
        [
            'supplier_id' => $demoUsers['supplier_a']->id,
            'item_name' => 'Chicken Whole (kg)',
        ],
        [
            'description' => 'Dressed whole chicken, chilled and packed same day.',
            'category' => 'meat',
            'unit' => 'kg',
            'price_per_unit' => 205,
            'available_stock' => 120,
            'minimum_order_quantity' => 5,
            'is_active' => true,
        ]
    );

    $listings['ginger'] = SupplierInventoryItem::updateOrCreate(
        [
            'supplier_id' => $demoUsers['supplier_a']->id,
            'item_name' => 'Ginger (kg)',
        ],
        [
            'description' => 'Fresh ginger for soups and sautés.',
            'category' => 'vegetables',
            'unit' => 'kg',
            'price_per_unit' => 90,
            'available_stock' => 60,
            'minimum_order_quantity' => 2,
            'is_active' => true,
        ]
    );

    $listings['malunggay'] = SupplierInventoryItem::updateOrCreate(
        [
            'supplier_id' => $demoUsers['supplier_b']->id,
            'item_name' => 'Malunggay (bunch)',
        ],
        [
            'description' => 'Fresh malunggay bunches.',
            'category' => 'vegetables',
            'unit' => 'bunch',
            'price_per_unit' => 16,
            'available_stock' => 150,
            'minimum_order_quantity' => 10,
            'is_active' => true,
        ]
    );

    $listings['monggo'] = SupplierInventoryItem::updateOrCreate(
        [
            'supplier_id' => $demoUsers['supplier_b']->id,
            'item_name' => 'Mung Beans (kg)',
        ],
        [
            'description' => 'Premium mung beans for monggo dishes.',
            'category' => 'grains',
            'unit' => 'kg',
            'price_per_unit' => 78,
            'available_stock' => 90,
            'minimum_order_quantity' => 5,
            'is_active' => true,
        ]
    );

    // 8) Suki relationship for preferred supplier workflow.
    KarenderiaSupplierSuki::firstOrCreate([
        'karenderia_id' => $karenderiaA->id,
        'supplier_id' => $demoUsers['supplier_a']->id,
    ]);

    // 9) Rebuild presentation supply orders with multiple statuses.
    $demoSupplyOrderIds = SupplyOrder::where('notes', 'like', 'PRESENTATION_DEMO:%')->pluck('id');
    if ($demoSupplyOrderIds->isNotEmpty()) {
        SupplyOrderItem::whereIn('supply_order_id', $demoSupplyOrderIds)->delete();
        SupplyOrder::whereIn('id', $demoSupplyOrderIds)->delete();
    }

    $createSupplyOrder = function (
        Karenderia $karenderia,
        User $supplier,
        string $status,
        array $items,
        string $note,
        ?string $deliveryDate = null
    ): SupplyOrder {
        $order = SupplyOrder::create([
            'karenderia_id' => $karenderia->id,
            'supplier_id' => $supplier->id,
            'status' => $status,
            'total_amount' => 0,
            'notes' => $note,
            'delivery_date' => $deliveryDate,
        ]);

        $total = 0;
        foreach ($items as $item) {
            $qty = (float) $item['quantity'];
            $price = (float) $item['listing']->price_per_unit;
            $lineTotal = $qty * $price;

            SupplyOrderItem::create([
                'supply_order_id' => $order->id,
                'supplier_inventory_item_id' => $item['listing']->id,
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => $lineTotal,
            ]);

            $total += $lineTotal;
        }

        $order->update(['total_amount' => $total]);

        return $order;
    };

    $createSupplyOrder(
        $karenderiaA,
        $demoUsers['supplier_a'],
        'pending',
        [
            ['listing' => $listings['chicken'], 'quantity' => 12],
            ['listing' => $listings['ginger'], 'quantity' => 5],
        ],
        'PRESENTATION_DEMO: pending owner order',
        now()->addDays(1)->toDateString()
    );

    $createSupplyOrder(
        $karenderiaA,
        $demoUsers['supplier_b'],
        'confirmed',
        [
            ['listing' => $listings['malunggay'], 'quantity' => 20],
            ['listing' => $listings['monggo'], 'quantity' => 8],
        ],
        'PRESENTATION_DEMO: confirmed supplier order',
        now()->addDays(2)->toDateString()
    );

    $createSupplyOrder(
        $karenderiaA,
        $demoUsers['supplier_a'],
        'delivered',
        [
            ['listing' => $listings['chicken'], 'quantity' => 15],
        ],
        'PRESENTATION_DEMO: delivered order for tracking',
        now()->subDays(1)->toDateString()
    );

    // 10) Sales history for owner performance insights.
    $demoOrderIds = DB::table('orders')
        ->where('order_number', 'like', 'DEMO2026-%')
        ->pluck('id');

    if ($demoOrderIds->isNotEmpty()) {
        DB::table('order_items')->whereIn('order_id', $demoOrderIds)->delete();
        DB::table('orders')->whereIn('id', $demoOrderIds)->delete();
    }

    $createCustomerOrder = function (
        string $orderNumber,
        int $customerId,
        int $karenderiaId,
        string $createdAt,
        array $items,
        float $deliveryFee = 35,
        float $serviceFee = 5,
        float $tax = 0
    ): void {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += ((float) $item['price']) * ((int) $item['qty']);
        }

        $total = $subtotal + $deliveryFee + $serviceFee + $tax;

        $orderId = DB::table('orders')->insertGetId([
            'order_number' => $orderNumber,
            'customer_id' => $customerId,
            'karenderia_id' => $karenderiaId,
            'status' => 'delivered',
            'payment_status' => 'paid',
            'payment_method' => 'gcash',
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'service_fee' => $serviceFee,
            'tax' => $tax,
            'total_amount' => $total,
            'total_cost' => $subtotal * 0.58,
            'delivery_address' => 'Makati CBD drop-off',
            'estimated_delivery_time' => $createdAt,
            'actual_delivery_time' => $createdAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        foreach ($items as $item) {
            $qty = (int) $item['qty'];
            $price = (float) $item['price'];

            DB::table('order_items')->insert([
                'order_id' => $orderId,
                'menu_item_id' => (int) $item['menu_item_id'],
                'quantity' => $qty,
                'unit_price' => $price,
                'unit_cost' => $price * 0.58,
                'total_price' => $qty * $price,
                'total_cost' => $qty * ($price * 0.58),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    };

    $createCustomerOrder(
        'DEMO2026-1001',
        $demoUsers['customer']->id,
        $karenderiaA->id,
        now()->subDays(4)->toDateTimeString(),
        [
            ['menu_item_id' => $menuItems['tinola']->id, 'qty' => 2, 'price' => 110.00],
            ['menu_item_id' => $menuItems['monggo']->id, 'qty' => 1, 'price' => 89.00],
        ]
    );

    $createCustomerOrder(
        'DEMO2026-1002',
        $demoUsers['customer']->id,
        $karenderiaA->id,
        now()->subDays(2)->toDateTimeString(),
        [
            ['menu_item_id' => $menuItems['kare_kare']->id, 'qty' => 1, 'price' => 195.00],
            ['menu_item_id' => $menuItems['tinola']->id, 'qty' => 1, 'price' => 110.00],
        ]
    );

    $createCustomerOrder(
        'DEMO2026-1003',
        $demoUsers['customer']->id,
        $karenderiaA->id,
        now()->subDay()->toDateTimeString(),
        [
            ['menu_item_id' => $menuItems['monggo']->id, 'qty' => 3, 'price' => 89.00],
        ],
        30,
        5,
        0
    );

    DB::commit();

    line('');
    line('Demo data created successfully.');
    line('');
    line('Use these accounts for screenshots:');
    line('Customer: demo.customer@kaplato.com / demo1234');
    line('Owner:    demo.owner@kaplato.com / owner1234');
    line('Supplier: demo.supplier1@kaplato.com / supplier1234');
    line('Supplier: demo.supplier2@kaplato.com / supplier1234');
    line('');
    line('Suggested flow to capture Slide 4:');
    line('1) Customer meal search with filters and allergen-safe alternatives');
    line('2) Owner menu + analytics with existing paid orders');
    line('3) Owner-supplier ordering with pending/confirmed/delivered statuses');
    line('');
    line('Try sample API checks:');
    line('GET /api/menu-items/search?karenderia=' . $karenderiaA->id);
    line('GET /api/karenderias/nearby?latitude=14.5560&longitude=121.0260&radius=3000');
    line('GET /api/supply/orders/owner (owner token)');
    line('GET /api/supply/orders/supplier (supplier token)');
    line('');
    line('Done.');
} catch (Throwable $e) {
    DB::rollBack();
    line('Failed to create presentation demo data.');
    line($e->getMessage());
    line($e->getTraceAsString());
    exit(1);
}
