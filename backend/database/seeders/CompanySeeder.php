<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::factory()->count(5)->create();
        $companies = Company::factory()->count(5)->create();
        foreach ($companies as $company) {
            $company->users()->attach($user->random(rand(1,3))->pluck('id')->toArray());
        }
    }
}
