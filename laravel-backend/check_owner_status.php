<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$karenderia = \App\Models\Karenderia::where('user_id', 59)->first();
if ($karenderia) {
    echo "Karenderia Status: " . $karenderia->status . "\n";
    echo "Rejection Reason: " . ($karenderia->rejection_reason ?? 'None') . "\n";
    echo "Rejected At: " . ($karenderia->rejected_at ? $karenderia->rejected_at->format('Y-m-d H:i:s') : 'Not set') . "\n";
} else {
    echo "Karenderia not found for user_id 59\n";
}
?>
