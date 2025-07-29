<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Purchase;

class TestFinancialSummary extends Command
{
    protected $signature = 'test:financial-summary';
    protected $description = 'Test financial summary calculation';

    public function handle()
    {
        $this->info('Testing Financial Summary Calculation:');
        $this->info('=====================================');

        // Get the user with the most purchases (Juan Dela Cruz - User 2)
        $user = User::find(2);
        if (!$user) {
            $this->error('User 2 not found!');
            return;
        }

        // Calculate financial summary
        $monthlyIncome = $user->salary ?? 0;
        $totalAssets = $user->purchases()->where('asset_type', 'asset')->sum('asset_value');
        $totalLiabilities = $user->purchases()->where('asset_type', 'liability')->sum('liability_amount');
        $totalExpenses = $user->purchases()->sum('amount'); // ALL expenses
        $netWorth = $totalAssets - $totalLiabilities; // Net Worth = Total Assets - Total Liabilities

        $this->info("User: {$user->name}");
        $this->info("Salary: ₱" . number_format($monthlyIncome, 2));
        $this->info("Total Assets: ₱" . number_format($totalAssets, 2));
        $this->info("Total Liabilities: ₱" . number_format($totalLiabilities, 2));
        $this->info("Total Expenses: ₱" . number_format($totalExpenses, 2));
        $this->info("Net Worth: ₱" . number_format($netWorth, 2));

        $this->info("\nBreakdown:");
        $this->info("==========");

        $assets = $user->purchases()->where('asset_type', 'asset')->get();
        $this->info("Assets ({$assets->count()} items):");
        foreach ($assets as $asset) {
            $this->info("  - {$asset->title}: ₱" . number_format($asset->asset_value, 2));
        }

        $liabilities = $user->purchases()->where('asset_type', 'liability')->get();
        $this->info("Liabilities ({$liabilities->count()} items):");
        foreach ($liabilities as $liability) {
            $this->info("  - {$liability->title}: ₱" . number_format($liability->liability_amount, 2));
        }

        $expenses = $user->purchases()->whereNull('asset_type')->get();
        $this->info("Expenses ({$expenses->count()} items):");
        foreach ($expenses as $expense) {
            $this->info("  - {$expense->title}: ₱" . number_format($expense->amount, 2));
        }
    }
}
