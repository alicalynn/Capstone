<?php
// Properly approve all users and suppliers in the system

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Karenderia;

echo "✅ APPROVING ALL USERS AND REMOVING PENDING STATUS\n";
echo "==================================================\n\n";

try {
    // Update all users
    $allUsers = User::all();
    
    foreach ($allUsers as $user) {
        // Set application_status to 'approved'
        $user->application_status = 'approved';
        // Set verified to true
        $user->verified = true;
        $user->save();
        
        echo "✅ Approved user: {$user->name} ({$user->email})\n";
    }

    echo "\n";
    
    // Update all karenderias to active status with approval info
    $karenderias = Karenderia::all();
    
    foreach ($karenderias as $karenderia) {
        $karenderia->status = 'active';
        $karenderia->approved_at = now();
        $karenderia->approved_by = 1; // Admin user ID
        $karenderia->save();
        
        echo "✅ Approved karenderia: {$karenderia->name}\n";
    }

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ ALL USERS, SUPPLIERS, AND KARENDERIAS FULLY APPROVED!  ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    
    echo "\nUpdates made:\n";
    echo "  ✓ All users: application_status = 'approved'\n";
    echo "  ✓ All users: verified = true\n";
    echo "  ✓ All karenderias: status = 'active'\n";
    echo "  ✓ All karenderias: approved_at = now()\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
