<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$email = 'testsupplier_ci+1@example.com';
$user = User::where('email', $email)->first();
if (!$user) {
    echo "NOTFOUND\n";
    exit(1);
}
$user->application_status = 'rejected';
$user->save();
echo "UPDATED\n";
