<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;

class CheckPurchases extends Command
{
    protected $signature = 'check:purchases';
    protected $description = 'Check purchase data and categorization';

    public function handle()
    {
        $this->info('Purchase Data Analysis:');
        $this->info('=====================');

        $total = Purchase::count();
        $assets = Purchase::where('asset_type', 'asset')->count();
        $liabilities = Purchase::where('asset_type', 'liability')->count();
        $expenses = Purchase::whereNull('asset_type')->count();

        $this->info("Total purchases: {$total}");
        $this->info("Assets: {$assets}");
        $this->info("Liabilities: {$liabilities}");
        $this->info("Expenses (null): {$expenses}");

        $this->info("\nSample purchases:");
        $this->info("================");

        $purchases = Purchase::select('title', 'amount', 'ai_categorized_category', 'asset_type')
            ->limit(10)
            ->get();

        foreach ($purchases as $purchase) {
            $this->info("{$purchase->title} - ₱" . number_format($purchase->amount, 2) . " ({$purchase->ai_categorized_category}) [Type: " . ($purchase->asset_type ?? 'null') . "]");
        }

        $this->info("\nCategories found:");
        $this->info("================");
        $categories = Purchase::select('ai_categorized_category')
            ->distinct()
            ->pluck('ai_categorized_category');

        foreach ($categories as $category) {
            $count = Purchase::where('ai_categorized_category', $category)->count();
            $this->info("{$category}: {$count} purchases");
        }
    }
}
