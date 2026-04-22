<?php
// Quick script to approve a supplier for testing

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\User;

try {
    // Find or create a test supplier
    $supplier = User::where('role', 'supplier')->first();
    
    if (!$supplier) {
        // Create test supplier if doesn't exist
        $supplier = User::create([
            'name' => 'Test Supplier',
            'email' => 'supplier@test.com',
            'password' => bcrypt('password123'),
            'phone_number' => '09123456789',
            'address' => 'Test Address',
            'role' => 'supplier',
            'verified' => false,
            'application_status' => 'pending'
        ]);
        echo "Created test supplier: " . $supplier->email . "\n";
    }
    
    // Approve the supplier
    $supplier->update([
        'application_status' => 'approved',
        'verified' => true
    ]);
    
    echo "✅ Supplier approved for testing!\n";
    echo "Email: " . $supplier->email . "\n";
    echo "Password: password123\n";
    echo "Status: " . $supplier->application_status . "\n";
    echo "\nYou can now log in and test the redirect.\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
