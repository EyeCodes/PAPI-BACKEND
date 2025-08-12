<?php

namespace App\Filament\Resources\UserResource\Widgets;

use Filament\Widgets\LineChartWidget;
use App\Models\User;
use Carbon\Carbon;

class UserRegistrationChart extends LineChartWidget
{
    protected static ?string $heading = 'User  Registrations';
    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $data = collect([]);

        // Last 12 months
        foreach (range(11, 0) as $i) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $data->put($month, 0);
        }

        $registrations = User::query()
            ->where('created_at', '>=', now()->subMonths(12))
            ->get()
            ->groupBy(fn ($user) => $user->created_at->format('Y-m'));

        foreach ($registrations as $month => $users) {
            if ($data->has($month)) {
                $data[$month] = $users->count();
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'User  Registrations',
                    'data' => $data->values(),
                    'borderColor' => '#3B82F6',
                    'backgroundColor' => 'rgba(59,130,246,0.4)',
                ],
            ],
            'labels' => $data->keys()->toArray(),
        ];
    }
}
