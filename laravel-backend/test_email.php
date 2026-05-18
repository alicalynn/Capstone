<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Messages\MailMessage;

try {
    // Test mail configuration
    echo "=== MAIL CONFIGURATION ===\n";
    echo "MAIL_MAILER: " . config('mail.default') . "\n";
    echo "MAIL_HOST: " . config('mail.mailers.smtp.host') . "\n";
    echo "MAIL_PORT: " . config('mail.mailers.smtp.port') . "\n";
    echo "MAIL_USERNAME: " . config('mail.from.address') . "\n";
    echo "MAIL_ENCRYPTION: " . config('mail.mailers.smtp.scheme') . "\n";
    echo "\n";
    
    // Check recent users
    echo "=== RECENT USERS ===\n";
    $users = DB::table('users')->latest('id')->take(3)->get(['id', 'name', 'email', 'role', 'email_notifications_enabled', 'created_at']);
    foreach ($users as $user) {
        echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email} | Role: {$user->role} | Email Notif: {$user->email_notifications_enabled} | Created: {$user->created_at}\n";
    }
    echo "\n";
    
    // Check jobs/notifications table for queued emails
    echo "=== QUEUED EMAILS (jobs table) ===\n";
    $jobs = DB::table('jobs')->get(['id', 'queue', 'payload', 'created_at']);
    if ($jobs->count() > 0) {
        echo "Found " . $jobs->count() . " jobs in queue\n";
        foreach ($jobs as $job) {
            echo "- Job ID: {$job->id} | Queue: {$job->queue} | Created: {$job->created_at}\n";
        }
    } else {
        echo "No jobs in queue\n";
    }
    echo "\n";
    
    // Test mail sending to actual registered user
    echo "=== TESTING MAIL SEND TO ACTUAL USER ===\n";
    $testUser = DB::table('users')->latest('id')->first(['id', 'name', 'email']);
    
    if ($testUser) {
        echo "Sending test email to: {$testUser->name} ({$testUser->email})\n\n";
        
        Mail::raw('This is a test email from KaPlato to verify your email registration.\n\nIf you received this, the email system is working correctly!', function ($message) use ($testUser) {
            $message->to($testUser->email)
                    ->subject('KaPlato Email System Test');
        });
        echo "✅ Test email sent successfully to {$testUser->email}\n";
    } else {
        echo "❌ No users found in database. Please register a user first.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
