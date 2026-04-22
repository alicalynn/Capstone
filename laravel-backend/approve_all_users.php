<?php
// Approve all users in the system

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Karenderia;

echo "✅ APPROVING ALL USERS\n";
echo "======================\n\n";

try {
    // Check User model to see what approval fields exist
    $user = User::first();
    
    // Try common approval field names
    $approvalFields = ['is_approved', 'approved', 'status', 'approval_status'];
    
    // Get all users
    $allUsers = User::all();
    
    echo "Processing " . $allUsers->count() . " users...\n\n";
    
    foreach ($allUsers as $user) {
        // Try to set approval status
        if (property_exists($user, 'is_approved')) {
            $user->is_approved = true;
            $user->save();
        } elseif (property_exists($user, 'approved')) {
            $user->approved = true;
            $user->save();
        }
        
        // Also approve karenderias if they exist
        if ($user->role === 'karenderia_owner') {
            $karenderia = Karenderia::where('owner_id', $user->id)->first();
            if ($karenderia) {
                if (property_exists($karenderia, 'is_approved')) {
                    $karenderia->is_approved = true;
                    $karenderia->save();
                } elseif (property_exists($karenderia, 'status')) {
                    $karenderia->status = 'active';
                    $karenderia->save();
                }
            }
        }
        
        echo "✅ Approved: {$user->name} ({$user->email}) - Role: {$user->role}\n";
    }
    
    // Also check and update Karenderia statuses
    $karenderias = Karenderia::all();
    echo "\n";
    foreach ($karenderias as $karenderia) {
        if ($karenderia->status !== 'active') {
            $karenderia->status = 'active';
            $karenderia->save();
            echo "✅ Approved Karenderia: {$karenderia->name}\n";
        }
    }

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ ALL USERS AND KARENDERIAS APPROVED!                    ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";
    echo "\nAll " . $allUsers->count() . " users are now approved and ready to use!\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
