<?php
// Create multiple owners and customers with karenderias

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Karenderia;
use App\Models\MenuItem;

echo "👥 CREATING MULTIPLE OWNERS AND CUSTOMERS\n";
echo "=========================================\n\n";

try {
    // Create additional owner accounts
    $ownerData = [
        [
            'name' => 'Maria Santos',
            'email' => 'maria@kaplato.com',
            'phone' => '+639555555555',
            'karenderia_name' => 'Maria\'s Eatery',
            'karenderia_description' => 'Home-cooked Filipino comfort food',
            'address' => '789 Mabini Street, Makati City',
        ],
        [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan@kaplato.com',
            'phone' => '+639666666666',
            'karenderia_name' => 'Juan\'s Food House',
            'karenderia_description' => 'Specialty Filipino and Asian fusion',
            'address' => '234 Ayala Avenue, Makati City',
        ],
        [
            'name' => 'Rosa Garcia',
            'email' => 'rosa@kaplato.com',
            'phone' => '+639777777777',
            'karenderia_name' => 'Rosa\'s Kitchen',
            'karenderia_description' => 'Budget-friendly authentic Filipino meals',
            'address' => '567 Edsa, Quezon City',
        ],
        [
            'name' => 'Antonio Reyes',
            'email' => 'antonio@kaplato.com',
            'phone' => '+639888888888',
            'karenderia_name' => 'Antonio\'s Kitchen & Bar',
            'karenderia_description' => 'Premium Filipino dining experience',
            'address' => '890 BGC, Fort Bonifacio',
        ],
        [
            'name' => 'Lucia Mendoza',
            'email' => 'lucia@kaplato.com',
            'phone' => '+639999999999',
            'karenderia_name' => 'Lucia\'s Lutuan',
            'karenderia_description' => 'Traditional regional Filipino cuisine',
            'address' => '321 España Boulevard, Manila',
        ],
    ];

    $owners = [];
    echo "🏪 Creating Owner Accounts and Karenderias\n";
    echo "==========================================\n\n";

    foreach ($ownerData as $data) {
        $owner = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => bcrypt('owner123'),
                'role' => 'karenderia_owner',
                'phone' => $data['phone'],
                'email_verified_at' => now()
            ]
        );
        $owners[] = $owner;
        echo "✅ Owner: {$data['name']} ({$data['email']})\n";

        // Create karenderia for each owner
        $karenderia = Karenderia::updateOrCreate(
            [
                'name' => $data['karenderia_name'],
                'owner_id' => $owner->id,
            ],
            [
                'business_name' => $data['karenderia_name'],
                'description' => $data['karenderia_description'],
                'address' => $data['address'],
                'city' => 'Metro Manila',
                'phone' => $data['phone'],
                'email' => $data['email'],
                'business_email' => $data['email'],
                'latitude' => 14.5500 + (rand(-50, 50) / 1000),
                'longitude' => 121.0250 + (rand(-50, 50) / 1000),
                'opening_time' => '06:00:00',
                'closing_time' => '21:00:00',
                'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
                'status' => 'active',
                'delivery_fee' => rand(40, 60),
                'delivery_time_minutes' => rand(20, 40),
                'accepts_cash' => true,
                'accepts_online_payment' => true,
                'average_rating' => rand(35, 50) / 10,
                'total_reviews' => rand(20, 100)
            ]
        );

        echo "   🏬 Karenderia: {$data['karenderia_name']}\n";
        echo "   📍 Address: {$data['address']}\n\n";
    }

    // Create additional customer accounts
    $customerData = [
        [
            'name' => 'Ana Torres',
            'email' => 'ana@gmail.com',
            'phone' => '+639111111111',
            'preferences' => 'vegetarian',
        ],
        [
            'name' => 'Carlos Fernandez',
            'email' => 'carlos@gmail.com',
            'phone' => '+639222222222',
            'preferences' => 'no_shellfish',
        ],
        [
            'name' => 'Diana Lopez',
            'email' => 'diana@gmail.com',
            'phone' => '+639333333333',
            'preferences' => 'low_sodium',
        ],
        [
            'name' => 'Eduardo Martinez',
            'email' => 'eduardo@gmail.com',
            'phone' => '+639444444444',
            'preferences' => 'gluten_free',
        ],
        [
            'name' => 'Francesca Rivera',
            'email' => 'francesca@gmail.com',
            'phone' => '+639555555556',
            'preferences' => 'no_peanuts',
        ],
        [
            'name' => 'Gabriel Santos',
            'email' => 'gabriel@gmail.com',
            'phone' => '+639666666667',
            'preferences' => 'halal',
        ],
        [
            'name' => 'Hannah Gonzales',
            'email' => 'hannah@gmail.com',
            'phone' => '+639777777778',
            'preferences' => 'vegan',
        ],
        [
            'name' => 'Ivan Reyes',
            'email' => 'ivan@gmail.com',
            'phone' => '+639888888889',
            'preferences' => 'dairy_free',
        ],
    ];

    $customers = [];
    echo "👨‍👩‍👧‍👦 Creating Customer Accounts\n";
    echo "=============================\n\n";

    foreach ($customerData as $data) {
        $customer = User::updateOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['name'],
                'password' => bcrypt('customer123'),
                'role' => 'customer',
                'phone' => $data['phone'],
                'email_verified_at' => now()
            ]
        );
        $customers[] = $customer;
        echo "✅ Customer: {$data['name']} ({$data['email']})\n";
        echo "   🏷️  Preferences: {$data['preferences']}\n\n";
    }

    echo "\n";
    echo "╔════════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ OWNERS AND CUSTOMERS CREATED SUCCESSFULLY!             ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n";

    echo "\n📊 Summary:\n";
    echo "  👨‍💼 Total Owners: " . count($owners) . " (+ 1 existing owner = 6 total)\n";
    echo "  🏪 Total Karenderias: " . count($owners) . " (+ 2 existing = " . (count($owners) + 2) . " total)\n";
    echo "  👥 Total Customers: " . count($customers) . " (+ 1 existing = " . (count($customers) + 1) . " total)\n";

    echo "\n📝 OWNER LOGIN CREDENTIALS (password: owner123):\n";
    foreach ($ownerData as $data) {
        echo "  • {$data['email']}\n";
    }

    echo "\n👨‍💼 CUSTOMER LOGIN CREDENTIALS (password: customer123):\n";
    foreach ($customerData as $data) {
        echo "  • {$data['email']} ({$data['preferences']})\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
