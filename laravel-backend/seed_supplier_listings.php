<?php
/**
 * Seed Supplier Listings for Testing
 * Usage: php seed_supplier_listings.php
 * This script creates sample supplier inventory items to test the owner-supplier workflow
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use App\Models\User;
use App\Models\SupplierInventoryItem;
use Illuminate\Support\Facades\DB;

// Find the approved supplier test account
$supplier = User::where('email', 'supplier.approved@kaplato.com')
    ->where('role', 'supplier')
    ->first();

if (!$supplier) {
    echo "❌ No approved supplier account found. Please run: php artisan migrate:fresh --seed\n";
    exit(1);
}

echo "✅ Found supplier: {$supplier->email}\n";
echo "📋 Seeding supplier inventory items...\n\n";

// Sample catalog data
$sampleCatalog = [
    // Meat
    [
        'item_name' => 'Fresh Chicken Breast',
        'description' => 'Daily-cut chicken breast for adobo, tinola, and fried meals.',
        'category' => 'Meat',
        'unit' => 'kg',
        'price_per_unit' => 190,
        'available_stock' => 80,
        'minimum_order_quantity' => 2,
    ],
    [
        'item_name' => 'Pork Kasim',
        'description' => 'Good for menudo, sinigang, and pork stew dishes.',
        'category' => 'Meat',
        'unit' => 'kg',
        'price_per_unit' => 210,
        'available_stock' => 70,
        'minimum_order_quantity' => 2,
    ],
    [
        'item_name' => 'Ground Pork',
        'description' => 'Fresh ground pork for chorizo, lumpia, and meat sauces.',
        'category' => 'Meat',
        'unit' => 'kg',
        'price_per_unit' => 185,
        'available_stock' => 50,
        'minimum_order_quantity' => 1,
    ],
    // Seafood
    [
        'item_name' => 'Whole Tilapia',
        'description' => 'Fresh tilapia sourced from local fish growers.',
        'category' => 'Seafood',
        'unit' => 'kg',
        'price_per_unit' => 165,
        'available_stock' => 90,
        'minimum_order_quantity' => 3,
    ],
    [
        'item_name' => 'Large Shrimp',
        'description' => 'Fresh jumbo shrimp for frying and garlic sauce dishes.',
        'category' => 'Seafood',
        'unit' => 'kg',
        'price_per_unit' => 420,
        'available_stock' => 30,
        'minimum_order_quantity' => 1,
    ],
    [
        'item_name' => 'Squid',
        'description' => 'Fresh squid perfect for adobo and sautéed preparations.',
        'category' => 'Seafood',
        'unit' => 'kg',
        'price_per_unit' => 280,
        'available_stock' => 40,
        'minimum_order_quantity' => 1,
    ],
    // Pantry
    [
        'item_name' => 'Cooking Oil',
        'description' => 'All-purpose vegetable cooking oil for deep frying and sautéing.',
        'category' => 'Pantry',
        'unit' => 'liter',
        'price_per_unit' => 78,
        'available_stock' => 200,
        'minimum_order_quantity' => 5,
    ],
    [
        'item_name' => 'Soy Sauce',
        'description' => 'Local soy sauce for marinade and seasoning.',
        'category' => 'Pantry',
        'unit' => 'liter',
        'price_per_unit' => 62,
        'available_stock' => 150,
        'minimum_order_quantity' => 3,
    ],
    [
        'item_name' => 'Vinegar',
        'description' => 'Calamansi or white vinegar for adobo and pickles.',
        'category' => 'Pantry',
        'unit' => 'liter',
        'price_per_unit' => 58,
        'available_stock' => 120,
        'minimum_order_quantity' => 2,
    ],
    [
        'item_name' => 'Brown Sugar',
        'description' => 'For sauces, marinades, and sweet dishes.',
        'category' => 'Pantry',
        'unit' => 'kg',
        'price_per_unit' => 74,
        'available_stock' => 80,
        'minimum_order_quantity' => 2,
    ],
    [
        'item_name' => 'Fish Sauce',
        'description' => 'Filipino patis for authentic dishes.',
        'category' => 'Pantry',
        'unit' => 'liter',
        'price_per_unit' => 85,
        'available_stock' => 90,
        'minimum_order_quantity' => 2,
    ],
    [
        'item_name' => 'Oyster Sauce',
        'description' => 'For stir-fries and Asian dishes.',
        'category' => 'Pantry',
        'unit' => 'bottle',
        'price_per_unit' => 95,
        'available_stock' => 70,
        'minimum_order_quantity' => 2,
    ],
    // Produce
    [
        'item_name' => 'Garlic',
        'description' => 'Fresh garlic bulbs for aromatics and sauces.',
        'category' => 'Produce',
        'unit' => 'kg',
        'price_per_unit' => 120,
        'available_stock' => 60,
        'minimum_order_quantity' => 1,
    ],
    [
        'item_name' => 'White Onion',
        'description' => 'Medium white onions for sauté and soup bases.',
        'category' => 'Produce',
        'unit' => 'kg',
        'price_per_unit' => 95,
        'available_stock' => 70,
        'minimum_order_quantity' => 1,
    ],
    [
        'item_name' => 'Red Onion',
        'description' => 'For sauces, pickles, and fresh garnish.',
        'category' => 'Produce',
        'unit' => 'kg',
        'price_per_unit' => 105,
        'available_stock' => 50,
        'minimum_order_quantity' => 1,
    ],
    [
        'item_name' => 'Tomato',
        'description' => 'Ripe tomatoes for stews and daily menus.',
        'category' => 'Produce',
        'unit' => 'kg',
        'price_per_unit' => 85,
        'available_stock' => 90,
        'minimum_order_quantity' => 1,
    ],
    [
        'item_name' => 'Calamansi',
        'description' => 'Fresh calamansi for dipping sauces and marinades.',
        'category' => 'Produce',
        'unit' => 'kg',
        'price_per_unit' => 110,
        'available_stock' => 45,
        'minimum_order_quantity' => 1,
    ],
    [
        'item_name' => 'Ginger',
        'description' => 'Fresh ginger root for soups and marinades.',
        'category' => 'Produce',
        'unit' => 'kg',
        'price_per_unit' => 95,
        'available_stock' => 35,
        'minimum_order_quantity' => 1,
    ],
    [
        'item_name' => 'Bell Pepper (Mixed)',
        'description' => 'Red, green, and yellow bell peppers for sauté.',
        'category' => 'Produce',
        'unit' => 'kg',
        'price_per_unit' => 140,
        'available_stock' => 40,
        'minimum_order_quantity' => 1,
    ],
    // Grains
    [
        'item_name' => 'Jasmine Rice',
        'description' => 'Premium rice ideal for all-day karenderia service.',
        'category' => 'Grains',
        'unit' => 'sack',
        'price_per_unit' => 1820,
        'available_stock' => 35,
        'minimum_order_quantity' => 1,
    ],
    [
        'item_name' => 'Mung Beans',
        'description' => 'For lugaw, monggo, and other Filipino dishes.',
        'category' => 'Grains',
        'unit' => 'kg',
        'price_per_unit' => 95,
        'available_stock' => 50,
        'minimum_order_quantity' => 2,
    ],
    // Packaging
    [
        'item_name' => 'Disposable Meal Box (25 pcs)',
        'description' => 'Takeout meal boxes bundled in packs of 25.',
        'category' => 'Packaging',
        'unit' => 'pack',
        'price_per_unit' => 95,
        'available_stock' => 120,
        'minimum_order_quantity' => 2,
    ],
    [
        'item_name' => 'Styrofoam Cup (100 pcs)',
        'description' => '12oz disposable cups for beverages.',
        'category' => 'Packaging',
        'unit' => 'pack',
        'price_per_unit' => 85,
        'available_stock' => 100,
        'minimum_order_quantity' => 2,
    ],
    [
        'item_name' => 'Plastic Bags',
        'description' => 'Small and medium plastic bags for food service.',
        'category' => 'Packaging',
        'unit' => 'pack',
        'price_per_unit' => 45,
        'available_stock' => 200,
        'minimum_order_quantity' => 5,
    ],
];

// Clear existing listings for this supplier
$deletedCount = SupplierInventoryItem::where('supplier_id', $supplier->id)->delete();
echo "🗑️  Cleared {$deletedCount} existing listings\n\n";

// Create listings
$createdCount = 0;
foreach ($sampleCatalog as $item) {
    $listing = SupplierInventoryItem::create([
        'supplier_id' => $supplier->id,
        'item_name' => $item['item_name'],
        'description' => $item['description'],
        'category' => $item['category'],
        'unit' => $item['unit'],
        'price_per_unit' => $item['price_per_unit'],
        'available_stock' => $item['available_stock'],
        'minimum_order_quantity' => $item['minimum_order_quantity'],
        'is_active' => true,
    ]);

    echo "✅ Created: {$item['item_name']} ({$item['unit']}) - ₱{$item['price_per_unit']}\n";
    $createdCount++;
}

echo "\n✨ Complete!\n";
echo "📊 Summary:\n";
echo "   • Supplier: {$supplier->email}\n";
echo "   • Listings Created: {$createdCount}\n";
echo "\n🧪 Test Workflow:\n";
echo "   1. Log in as karenderia owner: rosa.karenderia@email.com / password123\n";
echo "   2. Go to Suppliers section to see the listings\n";
echo "   3. Add items to cart and submit an order\n";
echo "   4. Log in as supplier: supplier.approved@kaplato.com / supplier123\n";
echo "   5. Go to Incoming Orders to process the order\n";
