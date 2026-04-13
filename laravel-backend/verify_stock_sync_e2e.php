<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\OrderController;
use App\Http\Controllers\SupplierWorkflowController;
use App\Models\Inventory;
use App\Models\Karenderia;
use App\Models\SupplierInventoryItem;
use App\Models\SupplyOrder;
use App\Models\SupplyOrderItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

function fail(string $message): void {
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
}

$owner = User::where('role', 'karenderia_owner')->first();
$supplier = User::where('role', 'supplier')->first();

if (!$owner) {
    fail('No karenderia_owner user found.');
}
if (!$supplier) {
    fail('No supplier user found.');
}

$karenderia = Karenderia::where('owner_id', $owner->id)->first();
if (!$karenderia) {
    fail('No karenderia linked to owner user.');
}

$tag = 'RICE_E2E_' . time();
$baseStock = 10.0;
$soldQty = 2.0;
$purchasedQty = 7.0;

$results = [
    'owner_id' => $owner->id,
    'supplier_id' => $supplier->id,
    'karenderia_id' => $karenderia->id,
    'item_name' => $tag,
    'initial_stock' => $baseStock,
    'sold_qty' => $soldQty,
    'purchased_qty' => $purchasedQty,
];

DB::beginTransaction();

try {
    // Seed owner kitchen stock with exact baseline.
    $inventory = Inventory::create([
        'karenderia_id' => $karenderia->id,
        'item_name' => $tag,
        'description' => 'E2E verification stock item',
        'category' => 'Test',
        'unit' => 'kg',
        'current_stock' => $baseStock,
        'minimum_stock' => 0,
        'maximum_stock' => 1000,
        'unit_cost' => 50,
        'total_value' => $baseStock * 50,
        'supplier' => 'E2E',
        'status' => 'available',
    ]);

    // Seed supplier listing and supply order.
    $listing = SupplierInventoryItem::create([
        'supplier_id' => $supplier->id,
        'item_name' => $tag,
        'description' => 'E2E listing',
        'category' => 'Test',
        'unit' => 'kg',
        'price_per_unit' => 60,
        'available_stock' => 100,
        'minimum_order_quantity' => 1,
        'is_active' => true,
    ]);

    $supplyOrder = SupplyOrder::create([
        'karenderia_id' => $karenderia->id,
        'supplier_id' => $supplier->id,
        'status' => 'pending',
        'total_amount' => $purchasedQty * 60,
        'notes' => 'E2E verification order',
    ]);

    SupplyOrderItem::create([
        'supply_order_id' => $supplyOrder->id,
        'supplier_inventory_item_id' => $listing->id,
        'quantity' => $purchasedQty,
        'unit_price' => 60,
        'line_total' => $purchasedQty * 60,
    ]);

    // Confirm order (should add purchased qty to owner kitchen stock).
    $supplierWorkflowController = new SupplierWorkflowController();
    $confirmRequest = Request::create('/api/supply/orders/' . $supplyOrder->id . '/status', 'PATCH', [
        'status' => 'confirmed',
    ]);
    $confirmRequest->setUserResolver(function () use ($supplier) {
        return $supplier;
    });

    $confirmResponse = $supplierWorkflowController->updateOrderStatus($confirmRequest, (int)$supplyOrder->id);
    if ($confirmResponse->getStatusCode() >= 400) {
        fail('Supply confirm failed with status ' . $confirmResponse->getStatusCode() . ': ' . $confirmResponse->getContent());
    }

    $inventory->refresh();
    $results['after_supply_confirmed'] = (float)$inventory->current_stock;

    // Cancel confirmed order (should roll back owner kitchen stock increment).
    $cancelRequest = Request::create('/api/supply/orders/' . $supplyOrder->id . '/status', 'PATCH', [
        'status' => 'cancelled',
    ]);
    $cancelRequest->setUserResolver(function () use ($supplier) {
        return $supplier;
    });

    $cancelResponse = $supplierWorkflowController->updateOrderStatus($cancelRequest, (int)$supplyOrder->id);
    if ($cancelResponse->getStatusCode() >= 400) {
        fail('Supply cancel failed with status ' . $cancelResponse->getStatusCode() . ': ' . $cancelResponse->getContent());
    }

    $inventory->refresh();
    $results['after_supply_cancelled'] = (float)$inventory->current_stock;

    // Reset baseline for sales deduction routine verification.
    $inventory->current_stock = $baseStock;
    $inventory->save();

    // Create menu item with explicit ingredient mapping to this stock item.
    $menuItemId = DB::table('menu_items')->insertGetId([
        'karenderia_id' => $karenderia->id,
        'name' => 'E2E Menu ' . $tag,
        'description' => 'E2E menu item',
        'price' => 120,
        'cost_price' => 60,
        'category' => 'Test',
        'is_available' => 1,
        'is_featured' => 0,
        'preparation_time_minutes' => 10,
        'ingredients' => json_encode([
            [
                'ingredientName' => $tag,
                'quantity' => 1,
                'unit' => 'kg',
                'cost' => 50,
            ],
        ]),
        'allergens' => json_encode([]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $orderId = DB::table('orders')->insertGetId([
        'order_number' => 'E2E-' . time() . '-' . random_int(100, 999),
        'customer_id' => $owner->id,
        'karenderia_id' => $karenderia->id,
        'status' => 'pending',
        'payment_status' => 'pending',
        'payment_method' => 'cash',
        'subtotal' => 240,
        'delivery_fee' => 0,
        'service_fee' => 0,
        'tax' => 0,
        'total_amount' => 240,
        'total_cost' => 120,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('order_items')->insert([
        'order_id' => $orderId,
        'menu_item_id' => $menuItemId,
        'quantity' => $soldQty,
        'unit_price' => 120,
        'unit_cost' => 60,
        'total_price' => 240,
        'total_cost' => 120,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Invoke completed-order deduction routine via reflection.
    $orderController = new OrderController();
    $reflection = new ReflectionClass($orderController);
    $method = $reflection->getMethod('deductKitchenStockForCompletedOrder');
    $method->setAccessible(true);
    $method->invoke($orderController, (int)$orderId, (int)$karenderia->id);

    $inventory->refresh();
    $results['after_completed_sale_deduction'] = (float)$inventory->current_stock;

    $results['expected_after_supply_confirmed'] = $baseStock + $purchasedQty;
    $results['expected_after_supply_cancelled'] = $baseStock;
    $results['expected_after_completed_sale_deduction'] = $baseStock - $soldQty;

    $results['supply_confirm_matches_expected'] = abs($results['after_supply_confirmed'] - $results['expected_after_supply_confirmed']) < 0.0001;
    $results['supply_cancel_matches_expected'] = abs($results['after_supply_cancelled'] - $results['expected_after_supply_cancelled']) < 0.0001;
    $results['sale_deduction_matches_expected'] = abs($results['after_completed_sale_deduction'] - $results['expected_after_completed_sale_deduction']) < 0.0001;

    echo json_encode($results, JSON_PRETTY_PRINT) . PHP_EOL;

    // Do not persist test fixtures.
    DB::rollBack();
} catch (Throwable $e) {
    DB::rollBack();
    fail('Verification failed: ' . $e->getMessage());
}
