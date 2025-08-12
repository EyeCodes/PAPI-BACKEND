<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyProgramRule;
use App\Models\LoyaltyReward;
use App\Models\BusinessType;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            BusinessTypeSeeder::class, // Ensure business types are seeded
        ]);

        // Create test user
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
 
        // Create additional users
        $otherUsers = User::factory()->count(5)->create();
        
        // Create companies
        $companies = Company::factory()->count(3)->create();
       
         // Assign random business types to companies
         foreach ($companies as $company) {
            $randomBusinessType = BusinessType::inRandomOrder()->first();
            $company->business_type_id = $randomBusinessType->id;
            $company->save();
        }
        
        // Attach users to companies (many-to-many)
        foreach ($companies as $company) {
            // Attach the test user to all companies
            $company->users()->attach($user->id);
            
            // Randomly attach 1-3 other users to each company
            $company->users()->attach(
                $otherUsers->random(rand(1, 3))->pluck('id')->toArray()
            );
        }
        $loyaltyprograms = LoyaltyProgram::factory()->count(3)->create();
        
        // Create a loyalty program for each company
        $loyaltyProgram = LoyaltyProgram::factory()->create([
            'company_id' => $company->id,
        ]);
        
       // Create loyalty program rules for the loyalty program
            LoyaltyProgramRule::factory()->count(3)->create([
                'loyalty_program_id' => $loyaltyProgram->id,
            ]);

        // Create loyalty rewards for each loyalty program rule
        foreach (LoyaltyProgramRule::all() as $rule) {
            LoyaltyReward::factory()->count(2)->create([
                'loyalty_program_rule_id' => $rule->id,
            ]);
}
}
}