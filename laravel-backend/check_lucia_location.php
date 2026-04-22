<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Karenderia;

$lucia = Karenderia::where('name', 'Lucia\'s Lutuan')->first();

if ($lucia) {
    echo "Lucia's Lutuan Details:\n";
    echo "========================\n";
    echo "ID: " . $lucia->id . "\n";
    echo "Status: " . $lucia->status . "\n";
    echo "Address: " . $lucia->address . "\n";
    echo "Latitude: " . $lucia->latitude . "\n";
    echo "Longitude: " . $lucia->longitude . "\n";
    echo "Owner ID: " . $lucia->owner_id . "\n";
    echo "\nTest Distance Calculation from Hipodromo:\n";
    
    // Hipodromo coordinates (from the map screenshot)
    $hipodromo_lat = 10.3157;
    $hipodromo_lng = 123.8854;
    
    // Simple distance calc
    $lat_diff = $lucia->latitude - $hipodromo_lat;
    $lng_diff = $lucia->longitude - $hipodromo_lng;
    $simple_distance = sqrt(($lat_diff * $lat_diff) + ($lng_diff * $lng_diff));
    
    echo "Hipodromo coordinates: $hipodromo_lat, $hipodromo_lng\n";
    echo "Distance (simple calc): $simple_distance degrees\n";
} else {
    echo "Lucia's Lutuan not found\n";
}
