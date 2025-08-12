<?php

namespace App\Filament\Widgets;

use App\Models\LoyaltyProgramRule;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class ActiveRulesStatsWidget extends ChartWidget
{
    protected static ?string $heading = 'Rules Activity (Last 30 Days)';
    
    protected static ?string $description = 'Daily rule creation trend';
    
    protected static ?int $sort = 5;
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $data = collect();
        
        // Get last 30 days
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = LoyaltyProgramRule::whereDate('created_at', $date)->count();
            
            $data->push([
                'date' => $date->format('M j'),
                'count' => $count,
            ]);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Rules Created',
                    'data' => $data->pluck('count')->toArray(),
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $data->pluck('date')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
