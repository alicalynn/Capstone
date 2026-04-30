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

function out(string $text = ''): void
{
    echo $text . PHP_EOL;
}

function pick(array $items)
{
    return $items[array_rand($items)];
}

out('=====================================================');
out('KaPlato Realistic Presentation Data Seeder');
out('=====================================================');

DB::beginTransaction();

try {
    $now = now();

    // 1) Owner and karenderia that should match the UI screenshots.
    $owner = User::updateOrCreate(
        ['email' => 'mikaela.santos@sukimeals.ph'],
        [
            'name' => 'Mikaela Santos',
            'password' => Hash::make('owner1234'),
            'role' => 'karenderia_owner',
            'verified' => true,
            'application_status' => 'approved',
            'email_verified_at' => $now,
            'phone_number' => '09181234567',
            'address' => 'Dela Rosa Street, Makati City',
        ]
    );

    // If an existing Suki Meals record exists, reuse it so current login still sees data.
    $primaryKarenderia = Karenderia::query()
        ->whereRaw('LOWER(COALESCE(business_name, name)) LIKE ?', ['%suki meals makati%'])
        ->orWhereRaw('LOWER(name) LIKE ?', ['%suki meals makati%'])
        ->first();

    if (!$primaryKarenderia) {
        $primaryKarenderia = new Karenderia();
    }

    $primaryKarenderia->fill([
        'owner_id' => $owner->id,
        'name' => 'Suki Meals Makati',
        'business_name' => 'Suki Meals Makati',
        'description' => 'Office-friendly Filipino meals with allergen-aware options and fast weekday delivery.',
        'address' => '120 Dela Rosa St, Legazpi Village, Makati City',
        'city' => 'Makati City',
        'province' => 'Metro Manila',
        'phone' => '09181234567',
        'email' => 'support@sukimeals.ph',
        'business_email' => 'support@sukimeals.ph',
        'latitude' => 14.5547,
        'longitude' => 121.0244,
        'opening_time' => '06:00:00',
        'closing_time' => '22:00:00',
        'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
        'status' => 'active',
        'approved_at' => $primaryKarenderia->approved_at ?: $now,
        'delivery_fee' => 29,
        'delivery_time_minutes' => 22,
        'accepts_cash' => true,
        'accepts_online_payment' => true,
        'average_rating' => 4.7,
        'total_reviews' => 1286,
    ]);
    $primaryKarenderia->save();

    // Normalize old demo naming if this account exists in this DB.
    User::where('email', 'demo.owner@kaplato.com')->update([
        'name' => 'Mikaela Santos',
        'role' => 'karenderia_owner',
        'verified' => true,
        'application_status' => 'approved',
        'phone_number' => '09181234567',
        'address' => 'Dela Rosa Street, Makati City',
    ]);

    $secondaryOwner = User::firstOrCreate(
        ['email' => 'nora.valencia@lutongbahay.ph'],
        [
            'name' => 'Nora Valencia',
            'password' => Hash::make('owner1234'),
            'role' => 'karenderia_owner',
            'verified' => true,
            'application_status' => 'approved',
            'email_verified_at' => $now,
            'phone_number' => '09183330001',
            'address' => 'Bautista Street, Poblacion, Makati City',
        ]
    );

    $secondaryKarenderia = Karenderia::updateOrCreate(
        ['owner_id' => $secondaryOwner->id],
        [
            'name' => 'Lutong Bahay ni Aling Nora',
            'business_name' => 'Lutong Bahay ni Aling Nora',
            'description' => 'Comfort food classics and healthy office lunch sets.',
            'address' => '248 Bautista St, Poblacion, Makati City',
            'city' => 'Makati City',
            'province' => 'Metro Manila',
            'phone' => '09183330001',
            'email' => 'hello@alingnora.ph',
            'business_email' => 'hello@alingnora.ph',
            'latitude' => 14.5622,
            'longitude' => 121.0321,
            'opening_time' => '06:30:00',
            'closing_time' => '21:00:00',
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'status' => 'active',
            'approved_at' => $now,
            'delivery_fee' => 24,
            'delivery_time_minutes' => 20,
            'accepts_cash' => true,
            'accepts_online_payment' => true,
            'average_rating' => 4.6,
            'total_reviews' => 912,
        ]
    );

    // 2) Realistic customers with allergen profiles.
    $customerProfiles = [
        ['name' => 'Paolo Dizon', 'email' => 'paolo.dizon@citymail.ph', 'allergens' => ['peanuts'], 'severity' => ['peanuts' => 'severe']],
        ['name' => 'Rica Fernandez', 'email' => 'rica.fernandez@citymail.ph', 'allergens' => ['shellfish'], 'severity' => ['shellfish' => 'moderate']],
        ['name' => 'Kenneth Lim', 'email' => 'kenneth.lim@citymail.ph', 'allergens' => ['eggs', 'soy'], 'severity' => ['eggs' => 'mild', 'soy' => 'moderate']],
        ['name' => 'Aubrey Manalo', 'email' => 'aubrey.manalo@citymail.ph', 'allergens' => ['dairy'], 'severity' => ['dairy' => 'moderate']],
        ['name' => 'Jules Bernardo', 'email' => 'jules.bernardo@citymail.ph', 'allergens' => ['fish'], 'severity' => ['fish' => 'mild']],
        ['name' => 'Miguel Asuncion', 'email' => 'miguel.asuncion@citymail.ph', 'allergens' => ['peanuts', 'shellfish'], 'severity' => ['peanuts' => 'severe', 'shellfish' => 'moderate']],
        ['name' => 'Camille Reyes', 'email' => 'camille.reyes@citymail.ph', 'allergens' => [], 'severity' => []],
        ['name' => 'Noel Pastor', 'email' => 'noel.pastor@citymail.ph', 'allergens' => ['gluten'], 'severity' => ['gluten' => 'mild']],
    ];

    $customers = [];
    foreach ($customerProfiles as $profile) {
        $customer = User::updateOrCreate(
            ['email' => $profile['email']],
            [
                'name' => $profile['name'],
                'password' => Hash::make('customer1234'),
                'role' => 'customer',
                'verified' => true,
                'application_status' => 'approved',
                'email_verified_at' => $now,
                'phone_number' => '09' . rand(100000000, 999999999),
                'address' => 'Makati City',
                'allergies' => $profile['allergens'],
                'dietary_restrictions' => [],
                'activity_level' => pick(['light', 'moderate', 'active']),
                'fitness_goal' => pick(['maintenance', 'weight_loss', 'muscle_gain']),
            ]
        );

        Allergen::where('user_id', $customer->id)->delete();
        foreach ($profile['allergens'] as $allergenName) {
            Allergen::create([
                'user_id' => $customer->id,
                'name' => $allergenName,
                'category' => in_array($allergenName, ['peanuts', 'gluten', 'soy'], true) ? 'food' : 'seafood',
                'severity' => $profile['severity'][$allergenName] ?? 'moderate',
                'notes' => 'Seeded realistic allergen profile',
            ]);
        }

        $customers[] = $customer;
    }

    // 3) Menu with realistic dish names and allergens.
    $primaryMenuData = [
        ['name' => 'Beef Kare-Kare sa Puso ng Saging', 'price' => 198, 'cost' => 118, 'cal' => 645, 'allergens' => ['peanuts'], 'ingredients' => ['beef', 'peanut sauce', 'eggplant', 'banana blossom', 'string beans'], 'orders' => 922],
        ['name' => 'Chicken Tinola ng Legazpi', 'price' => 129, 'cost' => 72, 'cal' => 335, 'allergens' => [], 'ingredients' => ['chicken', 'green papaya', 'ginger', 'malunggay'], 'orders' => 1154],
        ['name' => 'Ginisang Monggo with Tokwa', 'price' => 94, 'cost' => 46, 'cal' => 286, 'allergens' => ['soy'], 'ingredients' => ['mung beans', 'tofu', 'garlic', 'malunggay'], 'orders' => 1013],
        ['name' => 'Pancit Palabok Special', 'price' => 134, 'cost' => 76, 'cal' => 425, 'allergens' => ['shellfish', 'eggs'], 'ingredients' => ['rice noodles', 'shrimp', 'egg', 'garlic'], 'orders' => 741],
        ['name' => 'Adobong Manok sa Gata', 'price' => 142, 'cost' => 81, 'cal' => 488, 'allergens' => [], 'ingredients' => ['chicken', 'coconut milk', 'soy sauce', 'garlic'], 'orders' => 836],
        ['name' => 'Tofu Veggie Budget Bowl', 'price' => 99, 'cost' => 49, 'cal' => 302, 'allergens' => ['soy'], 'ingredients' => ['tofu', 'pechay', 'carrots', 'brown rice'], 'orders' => 678],
        ['name' => 'Ensaladang Talong with Inihaw na Tilapia', 'price' => 112, 'cost' => 59, 'cal' => 272, 'allergens' => ['fish'], 'ingredients' => ['eggplant', 'tilapia', 'tomato', 'onion'], 'orders' => 507],
        ['name' => 'Lumpiang Gulay Crunch', 'price' => 88, 'cost' => 42, 'cal' => 246, 'allergens' => ['gluten'], 'ingredients' => ['cabbage', 'carrot', 'wrapper', 'garlic'], 'orders' => 623],
        ['name' => 'Bistek Tagalog na May Sibuyas', 'price' => 156, 'cost' => 90, 'cal' => 455, 'allergens' => ['soy'], 'ingredients' => ['beef', 'soy sauce', 'calamansi', 'onion'], 'orders' => 592],
        ['name' => 'Sinigang na Baboy sa Sampalok', 'price' => 153, 'cost' => 87, 'cal' => 366, 'allergens' => [], 'ingredients' => ['pork', 'sampalok', 'kangkong', 'radish'], 'orders' => 864],
    ];

    $secondaryMenuData = [
        ['name' => 'Inasal Rice Plate', 'price' => 119, 'cost' => 66, 'cal' => 398, 'allergens' => [], 'ingredients' => ['chicken', 'annatto', 'garlic', 'rice'], 'orders' => 483],
        ['name' => 'Ginisang Ampalaya with Egg', 'price' => 92, 'cost' => 45, 'cal' => 218, 'allergens' => ['eggs'], 'ingredients' => ['ampalaya', 'egg', 'onion', 'garlic'], 'orders' => 352],
        ['name' => 'Pork Sisig Lunchbox', 'price' => 145, 'cost' => 83, 'cal' => 529, 'allergens' => ['soy'], 'ingredients' => ['pork', 'soy sauce', 'onion', 'chili'], 'orders' => 561],
        ['name' => 'Daing na Bangus Set', 'price' => 139, 'cost' => 77, 'cal' => 412, 'allergens' => ['fish'], 'ingredients' => ['bangus', 'garlic rice', 'tomato'], 'orders' => 417],
    ];

    $primaryMenuItems = [];
    foreach ($primaryMenuData as $dish) {
        $primaryMenuItems[] = MenuItem::updateOrCreate(
            ['karenderia_id' => $primaryKarenderia->id, 'name' => $dish['name']],
            [
                'description' => 'House bestseller from ' . $primaryKarenderia->business_name,
                'price' => $dish['price'],
                'cost_price' => $dish['cost'],
                'category' => 'main_course',
                'is_available' => true,
                'is_featured' => true,
                'preparation_time_minutes' => rand(12, 25),
                'calories' => $dish['cal'],
                'ingredients' => $dish['ingredients'],
                'allergens' => $dish['allergens'],
                'dietary_info' => empty($dish['allergens']) ? 'Allergen-safe for most diners' : 'Contains potential allergens',
                'spice_level' => rand(1, 2),
                'serving_size' => 1,
                'average_rating' => rand(42, 49) / 10,
                'total_reviews' => rand(140, 460),
                'total_orders' => $dish['orders'],
            ]
        );
    }

    $secondaryMenuItems = [];
    foreach ($secondaryMenuData as $dish) {
        $secondaryMenuItems[] = MenuItem::updateOrCreate(
            ['karenderia_id' => $secondaryKarenderia->id, 'name' => $dish['name']],
            [
                'description' => 'Customer favorite from ' . $secondaryKarenderia->business_name,
                'price' => $dish['price'],
                'cost_price' => $dish['cost'],
                'category' => 'main_course',
                'is_available' => true,
                'is_featured' => true,
                'preparation_time_minutes' => rand(10, 22),
                'calories' => $dish['cal'],
                'ingredients' => $dish['ingredients'],
                'allergens' => $dish['allergens'],
                'dietary_info' => empty($dish['allergens']) ? 'Allergen-safe for most diners' : 'Contains potential allergens',
                'spice_level' => rand(1, 2),
                'serving_size' => 1,
                'average_rating' => rand(40, 48) / 10,
                'total_reviews' => rand(80, 260),
                'total_orders' => $dish['orders'],
            ]
        );
    }

    // 4) Daily menu availability for meal browse and filtering screenshots.
    foreach ([$primaryKarenderia, $secondaryKarenderia] as $k) {
        DailyMenu::where('karenderia_id', $k->id)->whereDate('date', now()->toDateString())->delete();
    }

    $today = now()->toDateString();
    $mealTypes = ['breakfast', 'lunch', 'dinner'];

    foreach (array_slice($primaryMenuItems, 0, 8) as $idx => $item) {
        DailyMenu::create([
            'karenderia_id' => $primaryKarenderia->id,
            'menu_item_id' => $item->id,
            'date' => $today,
            'meal_type' => $mealTypes[$idx % 3],
            'quantity' => rand(25, 60),
            'original_quantity' => rand(25, 60),
            'is_available' => true,
            'notes' => 'Fresh batch for office rush',
        ]);
    }

    // 5) Inventory with low stock and out-of-stock alerts.
    $inventoryRows = [
        ['Chicken Whole (kg)', 'meat', 'kg', 22, 12, 50, 205, 'Metro Fresh Supply', 'available'],
        ['Malunggay (bunch)', 'vegetables', 'bunch', 4, 10, 40, 16, 'Green Basket Traders', 'low_stock'],
        ['Mung Beans (kg)', 'grains', 'kg', 7, 12, 45, 78, 'Green Basket Traders', 'low_stock'],
        ['Peanut Paste (kg)', 'condiments', 'kg', 9, 4, 20, 210, 'Sampaloc Dry Goods', 'available'],
        ['Brown Rice (kg)', 'grains', 'kg', 31, 20, 80, 64, 'RiceLine Distribution', 'available'],
        ['Tilapia (kg)', 'seafood', 'kg', 0, 6, 25, 165, 'Bay Fresh Seafood', 'out_of_stock'],
        ['Eggs (tray)', 'dairy', 'tray', 14, 8, 30, 248, 'Golden Egg Depot', 'available'],
        ['Soy Sauce (liter)', 'condiments', 'liter', 5, 8, 20, 96, 'Sampaloc Dry Goods', 'low_stock'],
        ['Onions (kg)', 'vegetables', 'kg', 26, 12, 45, 92, 'Green Basket Traders', 'available'],
        ['Garlic (kg)', 'vegetables', 'kg', 18, 9, 35, 145, 'Green Basket Traders', 'available'],
    ];

    foreach ($inventoryRows as [$name, $cat, $unit, $stock, $min, $max, $cost, $supplier, $status]) {
        Inventory::updateOrCreate(
            ['karenderia_id' => $primaryKarenderia->id, 'item_name' => $name],
            [
                'description' => $name,
                'category' => $cat,
                'unit' => $unit,
                'current_stock' => $stock,
                'minimum_stock' => $min,
                'maximum_stock' => $max,
                'unit_cost' => $cost,
                'total_value' => round($stock * $cost, 2),
                'supplier' => $supplier,
                'last_restocked' => now()->subDays(rand(1, 14))->toDateString(),
                'status' => $status,
            ]
        );
    }

    // 6) Suppliers, marketplace, suki relationships.
    $suppliers = [
        ['name' => 'Metro Fresh Supply Co.', 'email' => 'sales@metrofresh.ph', 'phone' => '09220001111'],
        ['name' => 'Green Basket Traders', 'email' => 'orders@greenbasket.ph', 'phone' => '09221112222'],
        ['name' => 'Bay Fresh Seafood', 'email' => 'trade@bayfresh.ph', 'phone' => '09223334444'],
    ];

    $supplierUsers = [];
    foreach ($suppliers as $s) {
        $supplierUsers[] = User::updateOrCreate(
            ['email' => $s['email']],
            [
                'name' => $s['name'],
                'password' => Hash::make('supplier1234'),
                'role' => 'supplier',
                'verified' => true,
                'application_status' => 'approved',
                'email_verified_at' => $now,
                'phone_number' => $s['phone'],
                'address' => 'Metro Manila',
            ]
        );
    }

    $catalog = [
        [$supplierUsers[0], 'Chicken Whole (kg)', 'meat', 'kg', 205, 260, 5],
        [$supplierUsers[0], 'Pork Kasim (kg)', 'meat', 'kg', 240, 180, 5],
        [$supplierUsers[0], 'Eggs (tray)', 'dairy', 'tray', 248, 95, 3],
        [$supplierUsers[1], 'Malunggay (bunch)', 'vegetables', 'bunch', 16, 420, 10],
        [$supplierUsers[1], 'Mung Beans (kg)', 'grains', 'kg', 78, 230, 5],
        [$supplierUsers[1], 'Brown Rice (kg)', 'grains', 'kg', 64, 500, 10],
        [$supplierUsers[2], 'Tilapia (kg)', 'seafood', 'kg', 165, 70, 3],
        [$supplierUsers[2], 'Shrimp Medium (kg)', 'seafood', 'kg', 380, 45, 2],
    ];

    $listingByName = [];
    foreach ($catalog as [$supplier, $name, $cat, $unit, $price, $stock, $moq]) {
        $listing = SupplierInventoryItem::updateOrCreate(
            ['supplier_id' => $supplier->id, 'item_name' => $name],
            [
                'description' => $name,
                'category' => $cat,
                'unit' => $unit,
                'price_per_unit' => $price,
                'available_stock' => $stock,
                'minimum_order_quantity' => $moq,
                'is_active' => true,
            ]
        );
        $listingByName[$name] = $listing;
    }

    KarenderiaSupplierSuki::firstOrCreate([
        'karenderia_id' => $primaryKarenderia->id,
        'supplier_id' => $supplierUsers[0]->id,
    ]);
    KarenderiaSupplierSuki::firstOrCreate([
        'karenderia_id' => $primaryKarenderia->id,
        'supplier_id' => $supplierUsers[1]->id,
    ]);

    // 7) Rebuild long-history customer orders (18 months).
    $oldOrderIds = DB::table('orders')->where('order_number', 'like', 'SMM-%')->pluck('id');
    if ($oldOrderIds->isNotEmpty()) {
        DB::table('order_items')->whereIn('order_id', $oldOrderIds)->delete();
        DB::table('orders')->whereIn('id', $oldOrderIds)->delete();
    }

    $menuPoolPrimary = collect($primaryMenuItems)->values();
    $menuPoolSecondary = collect($secondaryMenuItems)->values();

    $orderCounter = 1;
    for ($monthBack = 17; $monthBack >= 0; $monthBack--) {
        $monthBase = now()->subMonths($monthBack);
        $ordersThisMonth = rand(24, 34);

        for ($i = 0; $i < $ordersThisMonth; $i++) {
            $isPrimary = rand(1, 100) <= 74;
            $karenderia = $isPrimary ? $primaryKarenderia : $secondaryKarenderia;
            $pool = $isPrimary ? $menuPoolPrimary : $menuPoolSecondary;

            $customer = pick($customers);
            $orderDate = $monthBase->copy()->day(rand(1, min(28, $monthBase->daysInMonth)))->setTime(rand(7, 20), rand(0, 59), 0);

            $itemCount = rand(1, 3);
            $picked = $pool->random($itemCount);
            if ($picked instanceof \Illuminate\Support\Collection) {
                $picked = $picked->values();
            } else {
                $picked = collect([$picked]);
            }

            $subtotal = 0;
            $itemsForInsert = [];
            foreach ($picked as $menuItem) {
                $qty = rand(1, 3);
                $unitPrice = (float) $menuItem->price;
                $unitCost = (float) ($menuItem->cost_price ?: $unitPrice * 0.58);
                $subtotal += ($qty * $unitPrice);
                $itemsForInsert[] = [
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'total_price' => round($qty * $unitPrice, 2),
                    'total_cost' => round($qty * $unitCost, 2),
                ];
            }

            $deliveryFee = rand(20, 35);
            $serviceFee = 5;
            $tax = 0;
            $total = $subtotal + $deliveryFee + $serviceFee + $tax;

            $orderId = DB::table('orders')->insertGetId([
                'order_number' => 'SMM-' . $orderDate->format('Ym') . '-' . str_pad((string) $orderCounter, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'karenderia_id' => $karenderia->id,
                'status' => rand(1, 100) <= 92 ? 'delivered' : 'cancelled',
                'payment_status' => rand(1, 100) <= 90 ? 'paid' : 'failed',
                'payment_method' => pick(['cash', 'gcash', 'maya']),
                'subtotal' => round($subtotal, 2),
                'delivery_fee' => $deliveryFee,
                'service_fee' => $serviceFee,
                'tax' => $tax,
                'total_amount' => round($total, 2),
                'total_cost' => round($subtotal * 0.6, 2),
                'delivery_address' => $customer->address ?: 'Makati City',
                'special_instructions' => rand(1, 100) <= 20 ? 'Less salt please' : null,
                'estimated_delivery_time' => $orderDate->copy()->addMinutes(rand(22, 45))->toDateTimeString(),
                'actual_delivery_time' => $orderDate->copy()->addMinutes(rand(25, 52))->toDateTimeString(),
                'created_at' => $orderDate->toDateTimeString(),
                'updated_at' => $orderDate->toDateTimeString(),
            ]);

            foreach ($itemsForInsert as $row) {
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'menu_item_id' => $row['menu_item_id'],
                    'quantity' => $row['quantity'],
                    'unit_price' => $row['unit_price'],
                    'unit_cost' => $row['unit_cost'],
                    'total_price' => $row['total_price'],
                    'total_cost' => $row['total_cost'],
                    'special_instructions' => null,
                    'customizations' => null,
                    'created_at' => $orderDate->toDateTimeString(),
                    'updated_at' => $orderDate->toDateTimeString(),
                ]);
            }

            $orderCounter++;
        }
    }

    // Add a few current POS-friendly open orders.
    for ($i = 0; $i < 4; $i++) {
        $customer = pick($customers);
        $orderDate = now()->subMinutes(rand(15, 120));
        $menuItem = pick($primaryMenuItems);
        $qty = rand(1, 2);
        $subtotal = $qty * (float) $menuItem->price;

        $orderId = DB::table('orders')->insertGetId([
            'order_number' => 'SMM-TODAY-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT),
            'customer_id' => $customer->id,
            'karenderia_id' => $primaryKarenderia->id,
            'status' => pick(['pending', 'confirmed', 'preparing', 'ready']),
            'payment_status' => 'pending',
            'payment_method' => pick(['cash', 'gcash']),
            'subtotal' => round($subtotal, 2),
            'delivery_fee' => 0,
            'service_fee' => 0,
            'tax' => 0,
            'total_amount' => round($subtotal, 2),
            'total_cost' => round($subtotal * 0.6, 2),
            'delivery_address' => null,
            'special_instructions' => 'POS walk-in order',
            'estimated_delivery_time' => now()->addMinutes(20)->toDateTimeString(),
            'actual_delivery_time' => null,
            'created_at' => $orderDate->toDateTimeString(),
            'updated_at' => $orderDate->toDateTimeString(),
        ]);

        DB::table('order_items')->insert([
            'order_id' => $orderId,
            'menu_item_id' => $menuItem->id,
            'quantity' => $qty,
            'unit_price' => (float) $menuItem->price,
            'unit_cost' => (float) ($menuItem->cost_price ?: ($menuItem->price * 0.6)),
            'total_price' => round($subtotal, 2),
            'total_cost' => round($subtotal * 0.6, 2),
            'special_instructions' => null,
            'customizations' => null,
            'created_at' => $orderDate->toDateTimeString(),
            'updated_at' => $orderDate->toDateTimeString(),
        ]);
    }

    // Recalculate total_orders based on inserted order_items.
    $menuIds = collect($primaryMenuItems)->merge($secondaryMenuItems)->pluck('id');
    $orderCounts = DB::table('order_items')
        ->select('menu_item_id', DB::raw('SUM(quantity) as qty'))
        ->whereIn('menu_item_id', $menuIds)
        ->groupBy('menu_item_id')
        ->pluck('qty', 'menu_item_id');

    foreach ($menuIds as $menuId) {
        $qty = (int) ($orderCounts[$menuId] ?? 0);
        MenuItem::where('id', $menuId)->update([
            'total_orders' => max($qty, rand(300, 900)),
        ]);
    }

    // 8) Rebuild supplier order + delivery history (10 months).
    $oldSupplyIds = SupplyOrder::where('notes', 'like', 'REALISTIC_SEED:%')->pluck('id');
    if ($oldSupplyIds->isNotEmpty()) {
        SupplyOrderItem::whereIn('supply_order_id', $oldSupplyIds)->delete();
        SupplyOrder::whereIn('id', $oldSupplyIds)->delete();
    }

    $supplierListings = SupplierInventoryItem::whereIn('supplier_id', collect($supplierUsers)->pluck('id'))->get()->groupBy('supplier_id');

    for ($monthBack = 9; $monthBack >= 0; $monthBack--) {
        $monthBase = now()->subMonths($monthBack);

        for ($o = 0; $o < rand(4, 7); $o++) {
            $supplier = pick($supplierUsers);
            $createdAt = $monthBase->copy()->day(rand(1, min(26, $monthBase->daysInMonth)))->setTime(rand(8, 18), rand(0, 59));

            if ($monthBack <= 1) {
                $status = pick(['pending', 'confirmed', 'delivered']);
            } elseif ($monthBack <= 3) {
                $status = pick(['confirmed', 'delivered', 'delivered']);
            } else {
                $status = pick(['delivered', 'delivered', 'delivered', 'cancelled']);
            }

            $order = SupplyOrder::create([
                'karenderia_id' => $primaryKarenderia->id,
                'supplier_id' => $supplier->id,
                'status' => $status,
                'total_amount' => 0,
                'notes' => 'REALISTIC_SEED: monthly replenishment',
                'delivery_date' => $createdAt->copy()->addDays(rand(1, 4))->toDateString(),
                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => $createdAt->toDateTimeString(),
            ]);

            $items = $supplierListings[$supplier->id]->random(rand(1, min(3, $supplierListings[$supplier->id]->count())));
            if ($items instanceof \App\Models\SupplierInventoryItem) {
                $items = collect([$items]);
            }

            $total = 0;
            foreach ($items as $listing) {
                $qty = max((float) $listing->minimum_order_quantity, (float) rand(4, 18));
                $lineTotal = $qty * (float) $listing->price_per_unit;

                SupplyOrderItem::create([
                    'supply_order_id' => $order->id,
                    'supplier_inventory_item_id' => $listing->id,
                    'quantity' => $qty,
                    'unit_price' => (float) $listing->price_per_unit,
                    'line_total' => round($lineTotal, 2),
                    'created_at' => $createdAt->toDateTimeString(),
                    'updated_at' => $createdAt->toDateTimeString(),
                ]);

                $total += $lineTotal;
            }

            $order->update([
                'total_amount' => round($total, 2),
                'updated_at' => $createdAt->toDateTimeString(),
            ]);
        }
    }

    DB::commit();

    out('');
    out('Realistic presentation data seeded successfully.');
    out('');
    out('Primary Owner Login:');
    out('Email: mikaela.santos@sukimeals.ph');
    out('Password: owner1234');
    out('');
    out('Customer Login for allergen demo:');
    out('Email: paolo.dizon@citymail.ph');
    out('Password: customer1234');
    out('');
    out('Supplier Login:');
    out('Email: sales@metrofresh.ph');
    out('Password: supplier1234');
    out('');
    out('Seed summary:');
    out('- 2 active karenderias with realistic names');
    out('- allergen-tagged menu and daily offerings');
    out('- long-history orders + active POS orders');
    out('- inventory with low/out-of-stock alerts');
    out('- suki suppliers + multi-month supply order history');
    out('');
    out('Done.');
} catch (Throwable $e) {
    DB::rollBack();
    out('Failed to seed realistic data.');
    out($e->getMessage());
    out($e->getTraceAsString());
    exit(1);
}
