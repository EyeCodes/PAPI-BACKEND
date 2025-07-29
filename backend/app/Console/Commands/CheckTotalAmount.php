<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;

class CheckTotalAmount extends Command
{
    protected $signature = 'check:total-amount';
    protected $description = 'Check total amount in purchases table';

    public function handle()
    {
        $this->info('Total Amount Analysis:');
        $this->info('====================');

        $totalAmount = Purchase::sum('amount');
        $totalCount = Purchase::count();
        $averageAmount = $totalCount > 0 ? $totalAmount / $totalCount : 0;

        $this->info("Total amount: ₱" . number_format($totalAmount, 2));
        $this->info("Total purchases: {$totalCount}");
        $this->info("Average purchase: ₱" . number_format($averageAmount, 2));

        // Check by user
        $users = \App\Models\User::with('purchases')->get();
        foreach ($users as $user) {
            $userTotal = $user->purchases->sum('amount');
            $userCount = $user->purchases->count();
            $userAverage = $userCount > 0 ? $userTotal / $userCount : 0;

            $this->info("\nUser: {$user->name} (ID: {$user->id})");
            $this->info("  Total amount: ₱" . number_format($userTotal, 2));
            $this->info("  Total purchases: {$userCount}");
            $this->info("  Average purchase: ₱" . number_format($userAverage, 2));
        }

        // Show top 10 purchases by amount
        $this->info("\nTop 10 purchases by amount:");
        $this->info("============================");
        $topPurchases = Purchase::orderBy('amount', 'desc')->limit(10)->get();
        foreach ($topPurchases as $purchase) {
            $this->info("  {$purchase->title}: ₱" . number_format($purchase->amount, 2) . " (User: {$purchase->user_id})");
        }
    }
}
