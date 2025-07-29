<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions following Filament Shield naming convention
        $permissions = [
            // Merchant Resource permissions
            'view_merchant',
            'view_any_merchant',
            'create_merchant',
            'update_merchant',
            'restore_merchant',
            'restore_any_merchant',
            'replicate_merchant',
            'reorder_merchant',
            'delete_merchant',
            'delete_any_merchant',
            'force_delete_merchant',
            'force_delete_any_merchant',

            // Product permissions (for merchant products)
            'view_product',
            'view_any_product',
            'create_product',
            'update_product',
            'restore_product',
            'restore_any_product',
            'replicate_product',
            'reorder_product',
            'delete_product',
            'delete_any_product',
            'force_delete_product',
            'force_delete_any_product',

            // Points Rule permissions
            'view_points_rule',
            'view_any_points_rule',
            'create_points_rule',
            'update_points_rule',
            'restore_points_rule',
            'restore_any_points_rule',
            'replicate_points_rule',
            'reorder_points_rule',
            'delete_points_rule',
            'delete_any_points_rule',
            'force_delete_points_rule',
            'force_delete_any_points_rule',

            // Transaction permissions
            'view_transaction',
            'view_any_transaction',
            'create_transaction',
            'update_transaction',
            'restore_transaction',
            'restore_any_transaction',
            'replicate_transaction',
            'reorder_transaction',
            'delete_transaction',
            'delete_any_transaction',
            'force_delete_transaction',
            'force_delete_any_transaction',

            // User permissions
            'view_user',
            'view_any_user',
            'create_user',
            'update_user',
            'restore_user',
            'restore_any_user',
            'replicate_user',
            'reorder_user',
            'delete_user',
            'delete_any_user',
            'force_delete_user',
            'force_delete_any_user',

            // Role permissions
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
            'restore_role',
            'restore_any_role',
            'replicate_role',
            'reorder_role',
            'delete_role',
            'delete_any_role',
            'force_delete_role',
            'force_delete_any_role',

            // Permission permissions
            'view_permission',
            'view_any_permission',
            'create_permission',
            'update_permission',
            'restore_permission',
            'restore_any_permission',
            'replicate_permission',
            'reorder_permission',
            'delete_permission',
            'delete_any_permission',
            'force_delete_permission',
            'force_delete_any_permission',

            // Custom API permissions
            'api_view_products',
            'api_create_transaction',
            'api_calculate_points',
            'api_scan_qr',
            'api_redeem_points',
            'api_view_transactions',
            'api_view_profile',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $roles = [
            'super_admin' => $permissions,

            'admin' => [
                'view_merchant',
                'view_any_merchant',
                'create_merchant',
                'update_merchant',
                'delete_merchant',
                'delete_any_merchant',
                'view_product',
                'view_any_product',
                'create_product',
                'update_product',
                'delete_product',
                'delete_any_product',
                'view_points_rule',
                'view_any_points_rule',
                'create_points_rule',
                'update_points_rule',
                'delete_points_rule',
                'delete_any_points_rule',
                'view_transaction',
                'view_any_transaction',
                'create_transaction',
                'update_transaction',
                'delete_transaction',
                'delete_any_transaction',
                'view_user',
                'view_any_user',
                'create_user',
                'update_user',
                'delete_user',
                'delete_any_user',
                'view_role',
                'view_any_role',
                'create_role',
                'update_role',
                'delete_role',
                'delete_any_role',
                'view_permission',
                'view_any_permission',
                'api_view_products',
                'api_create_transaction',
                'api_calculate_points',
                'api_scan_qr',
                'api_redeem_points',
                'api_view_transactions',
                'api_view_profile',
            ],

            'merchant_manager' => [
                'view_merchant',
                'update_merchant',
                'view_product',
                'view_any_product',
                'create_product',
                'update_product',
                'delete_product',
                'delete_any_product',
                'view_points_rule',
                'view_any_points_rule',
                'create_points_rule',
                'update_points_rule',
                'delete_points_rule',
                'delete_any_points_rule',
                'view_transaction',
                'view_any_transaction',
                'create_transaction',
                'update_transaction',
                'api_view_products',
                'api_create_transaction',
                'api_calculate_points',
                'api_scan_qr',
                'api_redeem_points',
                'api_view_transactions',
                'api_view_profile',
            ],

            'cashier' => [
                'view_product',
                'view_any_product',
                'view_transaction',
                'view_any_transaction',
                'create_transaction',
                'update_transaction',
                'api_view_products',
                'api_create_transaction',
                'api_calculate_points',
                'api_scan_qr',
                'api_view_profile',
            ],

            'customer' => [
                'view_product',
                'view_any_product',
                'view_transaction',
                'api_view_products',
                'api_view_transactions',
                'api_view_profile',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::create(['name' => $roleName]);
            $role->givePermissionTo($rolePermissions);
        }

        // Assign roles to users
        $this->assignRolesToUsers();
    }

    private function assignRolesToUsers()
    {
        // Assign super_admin role to admin user
        $admin = User::where('email', 'admin@loyalty.com')->first();
        if ($admin) {
            $admin->assignRole('super_admin');
        }

        // Assign merchant_manager role to merchant users
        $merchantUsers = User::where('email', 'like', 'manager@%')->get();
        foreach ($merchantUsers as $merchantUser) {
            $merchantUser->assignRole('merchant_manager');
        }

        // Assign customer role to regular customers
        $customers = User::where('email', 'like', '%@example.com')
            ->where('email', 'not like', 'test%@example.com')
            ->get();
        foreach ($customers as $customer) {
            $customer->assignRole('customer');
        }

        // Assign customer role to test users
        $testUsers = User::where('email', 'like', 'test%@example.com')->get();
        foreach ($testUsers as $testUser) {
            $testUser->assignRole('customer');
        }
    }
}
