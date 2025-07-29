<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Merchant;
use App\Models\UserMerchantPoints;

class UserPointsSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::where('email', 'like', '%customer%')->get();
        $merchants = Merchant::all();

        if ($users->isEmpty() || $merchants->isEmpty()) {
            $this->command->warn('No users or merchants found. Skipping points seeding.');
            return;
        }

        foreach ($users as $user) {
            // Give each user points at different merchants
            foreach ($merchants as $index => $merchant) {
                // Skip some merchants to create variety
                if ($index % 2 === 0) {
                    continue;
                }

                $points = rand(10, 200); // Random points between 10-200
                $totalEarned = $points + rand(50, 500); // Total earned is more than current
                $totalSpent = rand(0, $totalEarned * 0.3); // Some points spent

                UserMerchantPoints::create([
                    'user_id' => $user->id,
                    'merchant_id' => $merchant->id,
                    'points' => $points,
                    'total_earned' => $totalEarned,
                    'total_spent' => $totalSpent,
                    'last_earned_at' => now()->subDays(rand(1, 30)),
                    'last_spent_at' => $totalSpent > 0 ? now()->subDays(rand(1, 15)) : null,
                ]);
            }
        }

        $this->command->info('Sample user points data created successfully!');
    }
}
