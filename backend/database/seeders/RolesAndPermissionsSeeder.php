<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\Company;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create roles
        $superadminRole = Role::firstOrCreate(['name' => 'superadmin']);
        $handlerRole = Role::firstOrCreate(['name' => 'handler']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create permissions (optional - you can add more specific permissions)
        $permissions = [
            'view_companies',
            'create_companies',
            'edit_companies',
            'delete_companies',
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_loyalty_programs',
            'use_point_calculator',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign all permissions to superadmin
        $superadminRole->syncPermissions(Permission::all());

        // Assign specific permissions to handler
        $handlerRole->syncPermissions([
            'view_companies',
            'edit_companies',
            'view_users',
            'create_users',
            'edit_users',
            'manage_loyalty_programs',
            'use_point_calculator',
        ]);

        // Create test users
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
            ]
        );
        $admin->assignRole('superadmin');

        $handler = User::firstOrCreate(
            ['email' => 'test@gmail.com'],
            [
                'name' => 'Handler Test',
                'password' => bcrypt('password'),
            ]
        );
        $handler->assignRole('handler');

        // Create a test company and assign handler to it
        $marriott = Company::firstOrCreate([
            'company_name' => 'Marriott Hotel',
            'email_contact_1' => 'contact@marriott.com',
            'telephone_contact_1' => '+1234567890',
            'region' => 'Region VI',
            'province' => 'Iloilo',
            'city_municipality' => 'Iloilo City',
            'barangay' => 'Molo',
            'zipcode' => '5000',
            'street' => '123 Hotel Street',
            'business_registration_number' => 'BRN12345',
            'tin_number' => '123456789',
            'is_active' => true,
        ]);

        // Attach handler to Marriott company
        if (!$handler->companies->contains($marriott->id)) {
            $handler->companies()->attach($marriott->id);
        }

        $this->command->info('Roles, permissions, and test users created successfully!');
    }
}