<?php

namespace Database\Seeders;

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Models\Shop;
use App\Models\ShopLocation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // First, seed industries (before shops, so shops can reference them)
        $this->call(IndustrySeeder::class);

        // Seed roles and permissions
        $this->call(RoleAndPermissionSeeder::class);

        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@byoe.test',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('admin');

        // Create 10 shop owners with shops and locations
        $this->command->info('Creating shops and locations...');
        for ($i = 1; $i <= 10; $i++) {
            $shopOwner = User::factory()->create([
                'name' => "Shop Owner $i",
                'email' => "shop{$i}@byoe.test",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $shopOwner->assignRole('shop_owner');

            $shop = Shop::factory()->create([
                'user_id' => $shopOwner->id,
                'status' => 'active',
            ]);

            // Create 2 locations per shop
            $primaryLocation = null;
            for ($j = 1; $j <= 2; $j++) {
                $location = ShopLocation::factory()->create([
                    'shop_id' => $shop->id,
                    'is_primary' => $j === 1,
                ]);
                if ($j === 1) {
                    $primaryLocation = $location;
                }
            }

            // Create 3 service requests per shop
            ServiceRequest::factory()->count(3)->create([
                'shop_location_id' => $primaryLocation->id,
            ]);
        }

        // Create 10 providers with profiles
        $this->command->info('Creating providers...');
        for ($i = 1; $i <= 10; $i++) {
            $providerUser = User::factory()->create([
                'name' => "Provider $i",
                'email' => "provider{$i}@byoe.test",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]);
            $providerUser->assignRole('provider');

            Provider::factory()->create([
                'user_id' => $providerUser->id,
                'bio' => "Experienced barista with {$i} years of expertise in coffee service and customer satisfaction.",
                'skills' => ['Barista', 'Latte Art', 'Customer Service', 'Espresso Expert'],
                'years_experience' => $i,
                'is_active' => true,
                'average_rating' => rand(40, 50) / 10, // 4.0 to 5.0
                'total_ratings' => rand(5, 20),
                'completed_bookings' => rand(10, 50),
            ]);
        }

        $this->command->newLine();
        $this->command->info('✅ Database seeded successfully!');
        $this->command->newLine();
        $this->command->info('🔐 Test Credentials:');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            [
                ['Admin', 'admin@byoe.test', 'password'],
                ['Shop Owner', 'shop1@byoe.test (through shop10@byoe.test)', 'password'],
                ['Provider', 'provider1@byoe.test (through provider10@byoe.test)', 'password'],
            ]
        );
        $this->command->newLine();
        $this->command->info('📊 Database Statistics:');
        $this->command->info('  - Users: 21 (1 admin, 10 shop owners, 10 providers)');
        $this->command->info('  - Shops: 10');
        $this->command->info('  - Shop Locations: 20');
        $this->command->info('  - Service Requests: 30 (3 per shop)');
        $this->command->info('  - Providers: 10');
        $this->command->newLine();
        $this->command->info('💡 Next Steps:');
        $this->command->info('  1. Log in as a shop owner to create service requests');
        $this->command->info('  2. Log in as a provider to view and accept requests');
        $this->command->info('  3. Complete the booking flow and test payments');
    }
}
