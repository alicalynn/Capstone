<?php
require __DIR__.'/bootstrap/app.php';
$app = app();
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$order = \App\Models\SupplyOrder::find(12);
if ($order) {
    echo "Order 12 Status: " . $order->status . "\n";
    echo "Order 12 Items: " . count($order->items) . "\n";
} else {
    echo "Order 12 not found\n";
}
