<?php

namespace App\Filament\Widgets;

use App\Models\LoyaltyProgramRule;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class LoyaltyRuleStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalRules = LoyaltyProgramRule::count();
        $activeRules = LoyaltyProgramRule::where('is_active', true)->count();
        $expiringSoon = LoyaltyProgramRule::where('active_to_date', '<=', now()->addDays(7))
            ->where('active_to_date', '>=', now())
            ->count();
        
        $rulesByType = LoyaltyProgramRule::select('rule_type', DB::raw('count(*) as count'))
            ->groupBy('rule_type')
            ->pluck('count', 'rule_type')
            ->toArray();
        
        $mostCommonType = array_key_exists('purchase_based', $rulesByType) ? 'Purchase Based' : 'Various';

        return [
            Stat::make('Total Rules', $totalRules)
                ->description('All loyalty program rules')
                ->descriptionIcon('heroicon-m-gift')
                ->color('primary'),
            
            Stat::make('Active Rules', $activeRules)
                ->description('Currently active rules')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Expiring Soon', $expiringSoon)
                ->description('Rules expiring within 7 days')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($expiringSoon > 0 ? 'warning' : 'success'),
            
            Stat::make('Most Common Type', $mostCommonType)
                ->description('Primary rule type in use')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}