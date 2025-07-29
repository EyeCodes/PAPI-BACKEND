<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Merchant;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TransactionStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();

        // Today's stats
        $todayTransactions = Transaction::whereDate('created_at', $today)->count();
        $todayAmount = Transaction::whereDate('created_at', $today)->sum('amount');
        $todayPoints = Transaction::whereDate('created_at', $today)->sum('awarded_points');

        // This month's stats
        $monthTransactions = Transaction::whereMonth('created_at', $thisMonth->month)->count();
        $monthAmount = Transaction::whereMonth('created_at', $thisMonth->month)->sum('amount');
        $monthPoints = Transaction::whereMonth('created_at', $thisMonth->month)->sum('awarded_points');

        // Last month's stats for comparison
        $lastMonthTransactions = Transaction::whereMonth('created_at', $lastMonth->month)->count();
        $lastMonthAmount = Transaction::whereMonth('created_at', $lastMonth->month)->sum('amount');
        $lastMonthPoints = Transaction::whereMonth('created_at', $lastMonth->month)->sum('awarded_points');

        // Calculate percentage changes
        $transactionChange = $lastMonthTransactions > 0
            ? (($monthTransactions - $lastMonthTransactions) / $lastMonthTransactions) * 100
            : 0;
        $amountChange = $lastMonthAmount > 0
            ? (($monthAmount - $lastMonthAmount) / $lastMonthAmount) * 100
            : 0;
        $pointsChange = $lastMonthPoints > 0
            ? (($monthPoints - $lastMonthPoints) / $lastMonthPoints) * 100
            : 0;

        // Top merchants
        $topMerchants = Transaction::select('merchant_id', DB::raw('COUNT(*) as transaction_count'), DB::raw('SUM(amount) as total_amount'))
            ->with('merchant')
            ->groupBy('merchant_id')
            ->orderBy('total_amount', 'desc')
            ->limit(3)
            ->get();

        return [
            Stat::make('Today\'s Transactions', $todayTransactions)
                ->description($todayAmount > 0 ? '₱' . number_format($todayAmount, 2) : 'No transactions')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('This Month\'s Transactions', $monthTransactions)
                ->description(
                    $transactionChange > 0
                        ? '+' . number_format($transactionChange, 1) . '% from last month'
                        : number_format($transactionChange, 1) . '% from last month'
                )
                ->descriptionIcon($transactionChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($transactionChange >= 0 ? 'success' : 'danger'),

            Stat::make('Total Amount This Month', '₱' . number_format($monthAmount, 2))
                ->description(
                    $amountChange > 0
                        ? '+' . number_format($amountChange, 1) . '% from last month'
                        : number_format($amountChange, 1) . '% from last month'
                )
                ->descriptionIcon($amountChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($amountChange >= 0 ? 'success' : 'danger'),

            Stat::make('Points Awarded This Month', number_format($monthPoints))
                ->description(
                    $pointsChange > 0
                        ? '+' . number_format($pointsChange, 1) . '% from last month'
                        : number_format($pointsChange, 1) . '% from last month'
                )
                ->descriptionIcon($pointsChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($pointsChange >= 0 ? 'success' : 'danger'),

            Stat::make('Total Customers', User::whereHas('roles', function ($query) {
                $query->where('name', 'customer');
            })->count())
                ->description('Active customers in the system')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Total Merchants', Merchant::count())
                ->description('Active merchants in the system')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('warning'),
        ];
    }
}
