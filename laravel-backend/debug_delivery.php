<?php
// Test script to debug the delivery confirmation error

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

try {
    // Get the order
    $order = \App\Models\SupplyOrder::find(12);
    
    if (!$order) {
        echo "Order 12 not found\n";
        exit(1);
    }
    
    echo "Order ID: " . $order->id . "\n";
    echo "Current Status: " . $order->status . "\n";
    echo "Supplier ID: " . $order->supplier_id . "\n";
    echo "Karenderia ID: " . $order->karenderia_id . "\n";
    
    // Test the getNextPossibleStatuses method
    $nextStatuses = $order->getNextPossibleStatuses();
    echo "Next Possible Statuses: " . json_encode($nextStatuses) . "\n";
    
    // Check if we can transition to delivered
    if (in_array('delivered', $nextStatuses)) {
        echo "Can transition to 'delivered': YES\n";
    } else {
        echo "Can transition to 'delivered': NO\n";
        echo "Available transitions: " . json_encode($nextStatuses) . "\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
