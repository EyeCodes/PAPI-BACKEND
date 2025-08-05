<?php

namespace App\Filament\Widgets;

use App\Models\LoyaltyProgramRule;
use App\Models\LoyaltyProgram;
use App\Models\Company;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class LoyaltyOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Session::get('selected_company_id', Company::first()?->id);
        
        if (!$companyId) {
            return [
                Stat::make('No Company Selected', '0')
                    ->description('Please select a company')
                    ->color('gray'),
            ];
        }

        // Get company-specific statistics
        $totalPrograms = LoyaltyProgram::where('company_id', $companyId)->count();
        
        $validPrograms = LoyaltyProgram::where('company_id', $companyId)
            ->whereHas('rules')
            ->count();
            
        $invalidPrograms = $totalPrograms - $validPrograms;
          
        $totalRules = LoyaltyProgramRule::whereHas('loyaltyProgram', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->count();
        
        $activeRules = LoyaltyProgramRule::whereHas('loyaltyProgram', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('is_active', true)->count();

        $avgPoints = LoyaltyProgramRule::whereHas('loyaltyProgram', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->where('is_active', true)->avg('points_earned') ?? 0;

        return [
            Stat::make('Total Programs', $totalPrograms)
                ->description($validPrograms . ' valid, ' . $invalidPrograms . ' invalid')
                ->descriptionIcon($invalidPrograms > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($invalidPrograms > 0 ? 'warning' : 'success'),
            
            Stat::make('Total Rules', $totalRules)
                ->description('Across all programs')
                ->descriptionIcon('heroicon-m-gift')
                ->color('primary'),
            
            Stat::make('Active Rules', $activeRules)
                ->description('Currently running')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Avg Points', number_format($avgPoints, 0))
                ->description('Per active rule')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('info'),
        ];
    }
}
