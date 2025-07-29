<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class FixAssetTypes extends Command
{
    protected $signature = 'fix:asset-types';
    protected $description = 'Fix asset_type field based on ai_categorized_category';

    public function handle()
    {
        $this->info('Fixing asset types based on AI categorization...');

        // Fix assets
        $assetCount = Purchase::where('ai_categorized_category', 'Asset')
            ->update(['asset_type' => 'asset']);
        $this->info("Updated {$assetCount} purchases to asset type");

        // Fix specific items that should be assets
        $landAssets = Purchase::where('title', 'land')
            ->update(['asset_type' => 'asset']);
        $this->info("Updated {$landAssets} land purchases to asset type");

        // Fix cars and vehicles
        $carAssets = Purchase::where('title', 'like', '%car%')
            ->orWhere('title', 'like', '%vehicle%')
            ->orWhere('title', 'like', '%ferrari%')
            ->orWhere('title', 'like', '%lamborghini%')
            ->update(['asset_type' => 'asset']);
        $this->info("Updated {$carAssets} vehicle purchases to asset type");

        // Fix houses and properties
        $houseAssets = Purchase::where('title', 'like', '%house%')
            ->orWhere('title', 'like', '%property%')
            ->orWhere('title', 'like', '%real estate%')
            ->update(['asset_type' => 'asset']);
        $this->info("Updated {$houseAssets} property purchases to asset type");

        // Fix liabilities (check for liability-related categories)
        $liabilityCount = Purchase::whereIn('ai_categorized_category', ['Liability', 'Credit', 'Loan'])
            ->update(['asset_type' => 'liability']);
        $this->info("Updated {$liabilityCount} purchases to liability type");

        // Fix specific liability items
        $loanLiabilities = Purchase::where('title', 'like', '%loan%')
            ->orWhere('title', 'like', '%debt%')
            ->orWhere('title', 'like', '%mortgage%')
            ->orWhere('title', 'like', '%credit%')
            ->update(['asset_type' => 'liability']);
        $this->info("Updated {$loanLiabilities} loan/debt purchases to liability type");

        // Set asset_value for assets
        $assetValueCount = Purchase::where('asset_type', 'asset')
            ->update(['asset_value' => DB::raw('amount')]);
        $this->info("Updated {$assetValueCount} assets with asset_value");

        // Set liability_amount for liabilities
        $liabilityAmountCount = Purchase::where('asset_type', 'liability')
            ->update(['liability_amount' => DB::raw('amount')]);
        $this->info("Updated {$liabilityAmountCount} liabilities with liability_amount");

        // Show results
        $this->info("\nFinal counts:");
        $this->info("Assets: " . Purchase::where('asset_type', 'asset')->count());
        $this->info("Liabilities: " . Purchase::where('asset_type', 'liability')->count());
        $this->info("Expenses: " . Purchase::whereNull('asset_type')->count());

        $this->info("\nSample fixed purchases:");
        $purchases = Purchase::select('title', 'amount', 'ai_categorized_category', 'asset_type', 'asset_value', 'liability_amount')
            ->limit(10)
            ->get();

        foreach ($purchases as $purchase) {
            $type = $purchase->asset_type ?? 'expense';
            $value = $purchase->asset_value ?? $purchase->liability_amount ?? $purchase->amount;
            $this->info("{$purchase->title} - ₱" . number_format($purchase->amount, 2) . " ({$purchase->ai_categorized_category}) [Type: {$type}, Value: ₱" . number_format($value, 2) . "]");
        }
    }
}
