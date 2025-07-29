<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin'),
            'points' => 0,
        ]);

        // Create merchant users
        $merchants = Merchant::all();

        foreach ($merchants as $merchant) {
            $merchantUser = User::create([
                'name' => $merchant->name . ' Manager',
                'email' => 'manager@' . strtolower(str_replace(' ', '', $merchant->name)) . '.com',
                'password' => Hash::make('password'),
                'points' => 0,
            ]);

            // Associate user with merchant
            $merchantUser->merchants()->attach($merchant->id);
        }

        // Create regular customers
        $customers = [
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.com',
            ],
            [
                'name' => 'Maria Santos',
                'email' => 'maria@example.com',
            ],
            [
                'name' => 'Pedro Reyes',
                'email' => 'pedro@example.com',
            ],
            [
                'name' => 'Ana Garcia',
                'email' => 'ana@example.com',
            ],
            [
                'name' => 'Luis Martinez',
                'email' => 'luis@example.com',
            ],
            [
                'name' => 'Carmen Lopez',
                'email' => 'carmen@example.com',
            ],
            [
                'name' => 'Roberto Torres',
                'email' => 'roberto@example.com',
            ],
            [
                'name' => 'Isabel Flores',
                'email' => 'isabel@example.com',
            ],
            [
                'name' => 'Miguel Rodriguez',
                'email' => 'miguel@example.com',
            ],
            [
                'name' => 'Sofia Herrera',
                'email' => 'sofia@example.com',
            ],
        ];

        foreach ($customers as $customerData) {
            User::create([
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'password' => Hash::make('password'),
                'points' => $customerData['points'],
            ]);
        }

        // Create test users for API testing
        $testUsers = [
            [
                'name' => 'Test Customer 1',
                'email' => 'test1@example.com',
                'points' => 0,
            ],
            [
                'name' => 'Test Customer 2',
                'email' => 'test2@example.com',
                'points' => 0,
            ],
            [
                'name' => 'Test Customer 3',
                'email' => 'test3@example.com',
                'points' => 0,
            ],
        ];

        foreach ($testUsers as $testUserData) {
            User::create([
                'name' => $testUserData['name'],
                'email' => $testUserData['email'],
                'password' => Hash::make('password'),
                'points' => $testUserData['points'],
            ]);
        }
    }
}
