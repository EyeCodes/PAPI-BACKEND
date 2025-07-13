<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Artisan;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
    $superAdmin = Role::firstOrCreate(['name' => 'super admin']);
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'merchant']);
        Role::firstOrCreate(['name' => 'customer']);

    $admin = User::firstOrCreate([
        'email' => 'super_admin@gmail.com',
    ], [
        'name' => 'Super Admin',
        'password' => bcrypt('super_admin'),
    ]);

    $admin->assignRole($superAdmin);

    Artisan::call('shield:generate');

    $superAdmin->givePermissionTo(Permission::all());
    }
}
