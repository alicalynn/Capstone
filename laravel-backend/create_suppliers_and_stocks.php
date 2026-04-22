<?php
// Create suppliers with inventory and stocks

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\SupplierInventoryItem;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderItem;
use App\Models\Karenderia;

echo "🏪 CREATING SUPPLIERS AND STOCKS\n";
echo "================================\n\n";

try {
    // Create supplier users
    $suppliers = [];
    
    $supplierData = [
        [
            'name' => 'Fresh Meat Supply Co.',
            'email' => 'supplier_meat@kaplato.com',
            'phone' => '+639111111111',
            'address' => '789 Market Street, Manila',
        ],
        [
            'name' => 'Golden Rice Trading',
            'email' => 'supplier_grains@kaplato.com',
            'phone' => '+639222222222',
            'address' => '321 Trading Ave, Quezon City',
        ],
        [
            'name' => 'Condiment Solutions Inc.',
            'email' => 'supplier_condiments@kaplato.com',
            'phone' => '+639333333333',
            'address' => '654 Industrial Park, Makati',
        ],
        [
            'name' => 'Fresh Vegetables & Produce',
            'email' => 'supplier_vegetables@kaplato.com',
            'phone' => '+639444444444',
            'address' => '432 Market District, Las Piñas',
        ],
    ];

    foreach ($supplierData as $data) {
        $supplier = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => bcrypt('supplier123'),
                'role' => 'supplier',
                'phone' => $data['phone'],
                'address' => $data['address'],
                'email_verified_at' => now()
            ]
        );
        $suppliers[] = $supplier;
        echo "✅ Created/Updated supplier: {$data['name']}\n";
    }

    echo "\n🏪 Creating Supplier Inventory Items and Stocks\n";
    echo "=============================================\n\n";

    // Get karenderias to create relationships
    $karenderias = Karenderia::all();

    // Create supplier inventory items and supply orders
    $inventoryItems = [
        [
            'item_name' => 'Chicken Breast (Frozen)',
            'description' => 'Premium frozen chicken breasts',
            'category' => 'meat',
            'unit' => 'kg',
            'price_per_unit' => 280.00,
            'minimum_order_quantity' => 10.0,
            'supplier_index' => 0,
        ],
        [
            'item_name' => 'Pork Shoulder',
            'description' => 'Fresh pork shoulder for roasting',
            'category' => 'meat',
            'unit' => 'kg',
            'price_per_unit' => 320.00,
            'minimum_order_quantity' => 8.0,
            'supplier_index' => 0,
        ],
        [
            'item_name' => 'Jasmine Rice (50kg bag)',
            'description' => 'Premium jasmine rice',
            'category' => 'grains',
            'unit' => 'bag',
            'price_per_unit' => 3250.00,
            'minimum_order_quantity' => 2.0,
            'supplier_index' => 1,
        ],
        [
            'item_name' => 'White Rice (50kg bag)',
            'description' => 'Standard white rice',
            'category' => 'grains',
            'unit' => 'bag',
            'price_per_unit' => 2800.00,
            'minimum_order_quantity' => 2.0,
            'supplier_index' => 1,
        ],
        [
            'item_name' => 'Soy Sauce (10L)',
            'description' => 'Premium soy sauce',
            'category' => 'condiments',
            'unit' => 'container',
            'price_per_unit' => 950.00,
            'minimum_order_quantity' => 1.0,
            'supplier_index' => 2,
        ],
        [
            'item_name' => 'Vinegar (10L)',
            'description' => 'Calamansi vinegar',
            'category' => 'condiments',
            'unit' => 'container',
            'price_per_unit' => 850.00,
            'minimum_order_quantity' => 1.0,
            'supplier_index' => 2,
        ],
        [
            'item_name' => 'Cabbage',
            'description' => 'Fresh cabbage',
            'category' => 'vegetables',
            'unit' => 'kg',
            'price_per_unit' => 45.00,
            'minimum_order_quantity' => 5.0,
            'supplier_index' => 3,
        ],
        [
            'item_name' => 'Carrots',
            'description' => 'Fresh carrots',
            'category' => 'vegetables',
            'unit' => 'kg',
            'price_per_unit' => 60.00,
            'minimum_order_quantity' => 5.0,
            'supplier_index' => 3,
        ],
        [
            'item_name' => 'Onions',
            'description' => 'Fresh yellow onions',
            'category' => 'vegetables',
            'unit' => 'kg',
            'price_per_unit' => 55.00,
            'minimum_order_quantity' => 5.0,
            'supplier_index' => 3,
        ],
        [
            'item_name' => 'Garlic',
            'description' => 'Fresh garlic cloves',
            'category' => 'vegetables',
            'unit' => 'kg',
            'price_per_unit' => 150.00,
            'minimum_order_quantity' => 3.0,
            'supplier_index' => 3,
        ],
    ];

    $createdItems = [];
    foreach ($inventoryItems as $item) {
        $supplier = $suppliers[$item['supplier_index']];
        
        $inventoryItem = SupplierInventoryItem::updateOrCreate(
            [
                'supplier_id' => $supplier->id,
                'item_name' => $item['item_name']
            ],
            [
                'description' => $item['description'],
                'category' => $item['category'],
                'unit' => $item['unit'],
                'price_per_unit' => $item['price_per_unit'],
                'minimum_order_quantity' => $item['minimum_order_quantity'],
                'is_active' => true,
                'available_stock' => rand(20, 100),
            ]
        );
        
        $createdItems[$item['item_name']] = $inventoryItem;
        echo "✅ Created inventory item: {$item['item_name']} (Stock: {$inventoryItem->available_stock} {$item['unit']})\n";
    }

    echo "\n📦 Creating Supply Orders for Karenderias\n";
    echo "==========================================\n\n";

    // Create supply orders linking suppliers to karenderias
    foreach ($karenderias as $karenderia) {
        // Each karenderia gets supplies from 2 random suppliers
        $randomSuppliers = collect($suppliers)->random(2);
        
        foreach ($randomSuppliers as $supplier) {
            // Get some items from this supplier
            $supplierItems = collect($createdItems)->filter(function($item) use ($supplier) {
                return $item->supplier_id === $supplier->id;
            });

            if ($supplierItems->isEmpty()) {
                continue;
            }

            $itemsToOrder = $supplierItems->count();
            $itemsToSelect = rand(1, min(2, $itemsToOrder));
            $selectedItems = $supplierItems->random($itemsToSelect);

            // Create supply order
            $supplyOrder = SupplyOrder::create([
                'supplier_id' => $supplier->id,
                'karenderia_id' => $karenderia->id,
                'status' => 'delivered',
                'total_amount' => 0,
                'delivery_date' => now()->subDays(rand(0, 4)),
                'notes' => 'Regular supply delivery'
            ]);

            $totalAmount = 0;
            foreach ($selectedItems as $item) {
                $quantity = rand(5, 20);
                $itemTotal = $quantity * $item->price_per_unit;
                $totalAmount += $itemTotal;

                SupplyOrderItem::create([
                    'supply_order_id' => $supplyOrder->id,
                    'supplier_inventory_item_id' => $item->id,
                    'quantity' => $quantity,
                    'unit_price' => $item->price_per_unit,
                    'line_total' => $itemTotal,
                ]);
            }

            $supplyOrder->update(['total_amount' => $totalAmount]);
            
            echo "✅ Created supply order #{$supplyOrder->id}: {$karenderia->name} ← {$supplier->name}\n";
            echo "   Items: " . $selectedItems->count() . " | Total: ₱" . number_format($totalAmount, 2) . "\n";
        }
    }

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ SUPPLIERS AND STOCKS CREATED SUCCESSFULLY!             ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\nSummary:\n";
    echo "  👥 Suppliers Created: " . count($suppliers) . "\n";
    echo "  📦 Inventory Items: " . count($createdItems) . "\n";
    echo "  🏪 Karenderias: " . $karenderias->count() . "\n";
    echo "\nSupplier Login Credentials:\n";
    foreach ($supplierData as $data) {
        echo "  - {$data['email']} / supplier123\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
