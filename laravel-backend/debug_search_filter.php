<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

// Check all users
echo "=== ALL USERS ===\n";
$allUsers = User::all();
echo "Total users: " . $allUsers->count() . "\n\n";

// Show first 5 users
echo "First 5 users:\n";
foreach ($allUsers->take(5) as $user) {
    echo "ID: {$user->id}, Name: {$user->name}, Email: {$user->email}\n";
}

echo "\n=== TEST CASE-INSENSITIVE SEARCH ===\n";

// Test the actual query being used
$search = 'allergen';
echo "Searching for: '$search'\n";

$query = User::query();
$query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);

echo "SQL: " . $query->toSql() . "\n";
echo "Bindings: " . json_encode($query->getBindings()) . "\n";

$results = $query->get();
echo "Results found: " . $results->count() . "\n";
foreach ($results as $user) {
    echo "  - {$user->name} ({$user->email})\n";
}

// Also try with uppercase
echo "\n=== TEST WITH UPPERCASE ===\n";
$search2 = 'ALLERGEN';
echo "Searching for: '$search2'\n";
$search2Lower = strtolower($search2);

$query2 = User::query();
$query2->whereRaw('LOWER(name) LIKE ?', ["%{$search2Lower}%"])
       ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search2Lower}%"]);

$results2 = $query2->get();
echo "Results found: " . $results2->count() . "\n";
foreach ($results2 as $user) {
    echo "  - {$user->name} ({$user->email})\n";
}

// Try a broader search to see what data exists
echo "\n=== SEARCH FOR PARTIAL MATCHES ===\n";
$search3 = 'e2e';
echo "Searching for: '$search3'\n";

$query3 = User::query();
$query3->whereRaw('LOWER(email) LIKE ?', ["%{$search3}%"]);

$results3 = $query3->get();
echo "Results found: " . $results3->count() . "\n";
foreach ($results3 as $user) {
    echo "  - {$user->email}\n";
}
?>
