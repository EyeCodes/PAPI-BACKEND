<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\UserRegistrationsChart;
use App\Models\Company;
use Filament\Forms\Concerns\InteractsWithForms;
use Illuminate\Contracts\Session\Session;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;

class Dashboard extends BaseDashboard
{
    // use InteractsWithForms;

    // protected static ?string $navigationIcon = 'heroicon-o-home';

    // protected static string $view = 'filament.pages.dashboard';
    
    // public ?array $data = [];

    // public function mount(): void
    // {
    //     $this->form->fill([
    //         'selected_company' => session()->get('selected_company', Company::first()?->id)
    //     ]);
    // }

    // public function form(Form $form): Form
    // {
    //     return $form->schema([
    //         Select::make('selected_company')
    //         ->label('Select Company')
    //         ->options(Company::pluck('company_name', 'id'))
    //         ->live()
    //         ->afterStateUpdated(function ($state) {
    //             session()->put('selected_company_id', $state);
    //             $this->dispatch('company-changed', ['company_id' => $state]);
    //         })
    //         ->required(),
    //     ])
    //     ->statePath('data');
    // }

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //        \App\Filament\Widgets\LoyaltyOverviewWidget::class,
    //     ];
    // }

    // public function getWidgets(): array
    // {
    //     return [
    //         \App\Filament\Widgets\ActiveRulesStatsWidget::class,
    //         \App\Filament\Widgets\LoyaltyRuleStatsWidget::class,
    //     ];
    // }

    // public function getSelectedCompanyId(): ?int
    // {
    //     return session()->get('selected_company_id');
    // }
}
