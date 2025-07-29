<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Merchant;
use App\Models\UserMerchantPoints;

class AddSamplePoints extends Command
{
    protected $signature = 'points:add-sample {--user= : Specific user ID to add points for} {--merchant= : Specific merchant ID to add points for}';

    protected $description = 'Add sample points data (100-500 points) for testing';

    public function handle()
    {
        $specificUserId = $this->option('user');
        $specificMerchantId = $this->option('merchant');

        // Get users
        if ($specificUserId) {
            $users = User::where('id', $specificUserId)->get();
        } else {
            $users = User::all();
        }

        if ($users->isEmpty()) {
            $this->error('No users found');
            return 1;
        }

        // Get merchants
        if ($specificMerchantId) {
            $merchants = Merchant::where('id', $specificMerchantId)->get();
        } else {
            $merchants = Merchant::all();
        }

        if ($merchants->isEmpty()) {
            $this->error('No merchants found');
            return 1;
        }

        $this->info("Adding points for {$users->count()} users across {$merchants->count()} merchants");

        $totalPointsAdded = 0;
        $totalRecordsCreated = 0;

        foreach ($users as $user) {
            $this->info("\nProcessing user: {$user->name} ({$user->email})");

            foreach ($merchants as $merchant) {
                // Generate random points between 100-500
                $points = rand(100, 500);
                $totalEarned = $points + rand(50, 200); // Total earned is more than current
                $totalSpent = rand(0, $totalEarned * 0.4); // Some points spent

                // Check if record already exists
                $existingRecord = UserMerchantPoints::where('user_id', $user->id)
                    ->where('merchant_id', $merchant->id)
                    ->first();

                if ($existingRecord) {
                    // Update existing record
                    $existingRecord->update([
                        'points' => $points,
                        'total_earned' => $totalEarned,
                        'total_spent' => $totalSpent,
                        'last_earned_at' => now()->subDays(rand(1, 30)),
                        'last_spent_at' => $totalSpent > 0 ? now()->subDays(rand(1, 15)) : null,
                    ]);
                    $this->line("  Updated: {$merchant->name} - {$points} points");
                } else {
                    // Create new record
                    UserMerchantPoints::create([
                        'user_id' => $user->id,
                        'merchant_id' => $merchant->id,
                        'points' => $points,
                        'total_earned' => $totalEarned,
                        'total_spent' => $totalSpent,
                        'last_earned_at' => now()->subDays(rand(1, 30)),
                        'last_spent_at' => $totalSpent > 0 ? now()->subDays(rand(1, 15)) : null,
                    ]);
                    $this->line("  Created: {$merchant->name} - {$points} points");
                }

                $totalPointsAdded += $points;
                $totalRecordsCreated++;
            }
        }

        $this->info("\n=== Summary ===");
        $this->info("Total records processed: {$totalRecordsCreated}");
        $this->info("Total points assigned: {$totalPointsAdded}");
        $this->info("Average points per user: " . round($totalPointsAdded / $users->count(), 2));
        $this->info("Average points per merchant: " . round($totalPointsAdded / $merchants->count(), 2));

        $this->info("\nSample points data created successfully!");
        return 0;
    }
}
