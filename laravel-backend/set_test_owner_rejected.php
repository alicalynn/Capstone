<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Karenderia;
use App\Models\User;

$owner = User::where('email', 'owner@kaplato.com')->first();

if (!$owner) {
    echo "ERROR: owner@kaplato.com not found\n";
    exit(1);
}

Karenderia::where('owner_id', $owner->id)->update([
    'status' => 'rejected',
    'rejection_reason' => 'Rejected for testing resubmission flow',
    'rejected_at' => now(),
    'approved_at' => null,
    'approved_by' => null,
]);

$owner->application_status = 'rejected';
$owner->verified = false;
$owner->save();

$karenderias = Karenderia::where('owner_id', $owner->id)
    ->orderBy('id')
    ->get(['id', 'name', 'status', 'rejection_reason', 'rejected_at'])
    ->toArray();

echo json_encode([
    'user' => [
        'id' => $owner->id,
        'email' => $owner->email,
        'application_status' => $owner->application_status,
        'verified' => $owner->verified,
    ],
    'karenderias' => $karenderias,
], JSON_PRETTY_PRINT) . PHP_EOL;
