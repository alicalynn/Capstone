<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Karenderia;

$karenderias = Karenderia::all();
foreach ($karenderias as $k) {
    echo $k->name . ": " . $k->status . "\n";
}
