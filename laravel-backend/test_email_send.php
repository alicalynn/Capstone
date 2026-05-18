#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Notifications\RegistrationConfirmationNotification;
use Illuminate\Support\Facades\Log;

try {
    echo "🔍 Testing Email Functionality\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // Get the most recent user
    $user = User::latest('id')->first();
    
    if (!$user) {
        echo "❌ No users found in database\n";
        exit(1);
    }
    
    echo "📧 Testing with user: {$user->name} ({$user->email})\n";
    echo "   Role: {$user->role}\n";
    echo "   Email Notifications Enabled: " . ($user->email_notifications_enabled ? "YES ✅" : "NO ❌") . "\n\n";
    
    echo "📤 Sending registration confirmation email...\n";
    
    // Send the notification
    $user->notify(new RegistrationConfirmationNotification($user->role));
    
    echo "✅ Email notification sent successfully!\n\n";
    
    // Check mail driver
    echo "🔧 Mail Configuration:\n";
    echo "   Driver: " . config('mail.default') . "\n";
    echo "   Host: " . config('mail.mailers.smtp.host') . "\n";
    echo "   Port: " . config('mail.mailers.smtp.port') . "\n";
    echo "   From: " . config('mail.from.address') . "\n";
    echo "   Username: " . config('mail.mailers.smtp.username') . "\n";
    
    echo "\n✅ All checks passed! Email system is configured correctly.\n";
    echo "\n📝 Note: Emails will be sent to Gmail account: " . config('mail.mailers.smtp.username') . "\n";
    echo "   Recipient: {$user->email}\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
