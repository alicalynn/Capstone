<?php

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$ownerEmail = file_get_contents('owner_test_email.txt');
$ownerEmail = trim($ownerEmail);

$user = App\Models\User::where('email', $ownerEmail)->first();
if ($user && $user->karenderia) {
    $user->karenderia->update([
        'status' => 'rejected',
        'rejection_reason' => 'Test rejection for reapply flow',
        'rejected_at' => now()
    ]);
    echo "✅ Owner karenderia set to rejected status\n";
    echo "Email: $ownerEmail\n";
} else {
    echo "❌ Owner or karenderia not found\n";
}
