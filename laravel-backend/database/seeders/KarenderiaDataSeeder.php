<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Karenderia;
use App\Models\MenuItem;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class KarenderiaDataSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Get the karenderia owner
        $owner = User::where('email', 'owner@kaplato.com')->first();
        $customer = User::where('email', 'customer@kaplato.com')->first();
        
        if (!$owner || !$customer) {
            $this->command->error('Please run AdminUserSeeder first!');
            return;
        }

        // Create sample karenderias
        $karenderia1 = Karenderia::firstOrCreate([
            'name' => 'Lola Maria\'s Kitchen',
            'owner_id' => $owner->id,
        ], [
            'business_name' => 'Lola Maria\'s Kitchen',
            'description' => 'Authentic Filipino home-cooked meals served with love',
            'address' => '123 Rizal Street, Makati City, Metro Manila',
            'phone' => '+639123456789',
            'email' => 'lolakitchen@example.com',
            'business_email' => 'lolakitchen@example.com',
            'latitude' => 14.5547,
            'longitude' => 121.0244,
            'opening_time' => '06:00:00',
            'closing_time' => '21:00:00',
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
            'status' => 'active',
            'delivery_fee' => 50.00,
            'delivery_time_minutes' => 30,
            'accepts_cash' => true,
            'accepts_online_payment' => true,
            'average_rating' => 4.5,
            'total_reviews' => 87
        ]);

        $karenderia2 = Karenderia::firstOrCreate([
            'name' => 'Tita Linda\'s Lutong Bahay',
            'owner_id' => $owner->id,
        ], [
            'business_name' => 'Tita Linda\'s Lutong Bahay',
            'description' => 'Traditional Filipino comfort food',
            'address' => '456 Dela Rosa Avenue, Quezon City, Metro Manila',
            'phone' => '+639987654321',
            'email' => 'titalinda@example.com',
            'business_email' => 'titalinda@example.com',
            'latitude' => 14.6760,
            'longitude' => 121.0437,
            'opening_time' => '07:00:00',
            'closing_time' => '20:00:00',
            'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'],
            'status' => 'active',
            'delivery_fee' => 45.00,
            'delivery_time_minutes' => 25,
            'accepts_cash' => true,
            'accepts_online_payment' => false,
            'average_rating' => 4.2,
            'total_reviews' => 64
        ]);

        // Create menu items for Lola Maria's Kitchen
        $menuItems1 = [
            [
                'name' => 'Adobong Manok',
                'description' => 'Classic Filipino chicken adobo cooked in soy sauce and vinegar',
                'price' => 120.00,
                'cost_price' => 70.00,
                'category' => 'main_course',
                'is_available' => true,
                'is_featured' => true,
                'preparation_time_minutes' => 20,
                'calories' => 350,
                'ingredients' => ['chicken', 'soy sauce', 'vinegar', 'garlic', 'bay leaves'],
                'allergens' => ['soy'],
                'spice_level' => 2,
                'average_rating' => 4.7,
                'total_reviews' => 45,
                'total_orders' => 123
            ],
            [
                'name' => 'Sinigang na Baboy',
                'description' => 'Sour pork soup with vegetables',
                'price' => 150.00,
                'cost_price' => 90.00,
                'category' => 'main_course',
                'is_available' => true,
                'is_featured' => true,
                'preparation_time_minutes' => 25,
                'calories' => 280,
                'ingredients' => ['pork', 'tamarind', 'kangkong', 'radish', 'tomatoes'],
                'allergens' => [],
                'spice_level' => 1,
                'average_rating' => 4.5,
                'total_reviews' => 32,
                'total_orders' => 89
            ],
            [
                'name' => 'Pancit Canton',
                'description' => 'Stir-fried noodles with vegetables and meat',
                'price' => 100.00,
                'cost_price' => 60.00,
                'category' => 'main_course',
                'is_available' => true,
                'preparation_time_minutes' => 15,
                'calories' => 320,
                'ingredients' => ['canton noodles', 'cabbage', 'carrots', 'pork', 'shrimp'],
                'allergens' => ['gluten', 'shellfish'],
                'spice_level' => 1,
                'average_rating' => 4.3,
                'total_reviews' => 28,
                'total_orders' => 76
            ],
            [
                'name' => 'Halo-Halo',
                'description' => 'Filipino shaved ice dessert with mixed ingredients',
                'price' => 80.00,
                'cost_price' => 45.00,
                'category' => 'dessert',
                'is_available' => true,
                'preparation_time_minutes' => 10,
                'calories' => 250,
                'ingredients' => ['shaved ice', 'ube', 'leche flan', 'beans', 'corn'],
                'allergens' => ['dairy'],
                'spice_level' => 0,
                'average_rating' => 4.6,
                'total_reviews' => 41,
                'total_orders' => 95
            ]
        ];

        foreach ($menuItems1 as $item) {
            MenuItem::firstOrCreate([
                'karenderia_id' => $karenderia1->id,
                'name' => $item['name']
            ], array_merge($item, ['karenderia_id' => $karenderia1->id]));
        }

        // Create menu items for Tita Linda's
        $menuItems2 = [
            [
                'name' => 'Beef Kare-Kare',
                'description' => 'Oxtail and vegetables in peanut sauce',
                'price' => 180.00,
                'cost_price' => 110.00,
                'category' => 'main_course',
                'is_available' => true,
                'is_featured' => true,
                'preparation_time_minutes' => 30,
                'calories' => 420,
                'ingredients' => ['oxtail', 'peanut sauce', 'eggplant', 'string beans'],
                'allergens' => ['peanuts'],
                'spice_level' => 1,
                'average_rating' => 4.4,
                'total_reviews' => 25,
                'total_orders' => 67
            ],
            [
                'name' => 'Lumpia Shanghai',
                'description' => 'Filipino spring rolls with ground pork',
                'price' => 90.00,
                'cost_price' => 50.00,
                'category' => 'appetizer',
                'is_available' => true,
                'preparation_time_minutes' => 12,
                'calories' => 180,
                'ingredients' => ['ground pork', 'carrots', 'spring roll wrapper'],
                'allergens' => ['gluten'],
                'spice_level' => 1,
                'average_rating' => 4.5,
                'total_reviews' => 33,
                'total_orders' => 88
            ]
        ];

        foreach ($menuItems2 as $item) {
            MenuItem::firstOrCreate([
                'karenderia_id' => $karenderia2->id,
                'name' => $item['name']
            ], array_merge($item, ['karenderia_id' => $karenderia2->id]));
        }

        // Create sample inventory for karenderias
        $inventoryItems1 = [
            [
                'item_name' => 'Chicken (whole)',
                'description' => 'Fresh whole chicken',
                'category' => 'meat',
                'unit' => 'kg',
                'current_stock' => 25.5,
                'minimum_stock' => 10.0,
                'maximum_stock' => 50.0,
                'unit_cost' => 180.00,
                'supplier' => 'Fresh Meat Supply Co.',
                'status' => 'available'
            ],
            [
                'item_name' => 'Rice (Jasmine)',
                'description' => 'Premium jasmine rice',
                'category' => 'grains',
                'unit' => 'kg',
                'current_stock' => 8.0,
                'minimum_stock' => 15.0,
                'maximum_stock' => 100.0,
                'unit_cost' => 65.00,
                'supplier' => 'Golden Rice Trading',
                'status' => 'low_stock'
            ],
            [
                'item_name' => 'Soy Sauce',
                'description' => 'Premium soy sauce',
                'category' => 'condiments',
                'unit' => 'liters',
                'current_stock' => 12.5,
                'minimum_stock' => 5.0,
                'maximum_stock' => 30.0,
                'unit_cost' => 95.00,
                'supplier' => 'Condiment Solutions Inc.',
                'status' => 'available'
            ]
        ];

        foreach ($inventoryItems1 as $item) {
            $inventory = array_merge($item, ['karenderia_id' => $karenderia1->id]);
            $inventory['total_value'] = $inventory['current_stock'] * $inventory['unit_cost'];
            $inventory['last_restocked'] = now()->subDays(rand(1, 30));
            
            Inventory::firstOrCreate([
                'karenderia_id' => $karenderia1->id,
                'item_name' => $item['item_name']
            ], $inventory);
        }

        $this->command->info('Sample karenderia data created successfully!');
        $this->command->info('- 2 Karenderias');
        $this->command->info('- 6 Menu Items');
        $this->command->info('- 3 Inventory Items');
    }
}
