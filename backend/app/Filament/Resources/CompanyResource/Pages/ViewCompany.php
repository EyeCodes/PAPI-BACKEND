<?php
namespace App\Filament\Resources\CompanyResource\Pages;

use App\Filament\Resources\CompanyResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;

class ViewCompany extends ViewRecord
{
    protected static string $resource = CompanyResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\LoyaltyOverviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Company Information')
                    ->schema([
                        ImageEntry::make('company_logo')
                        ->label('Company Logo')
                        ->size(150),
                        TextEntry::make('company_name')
                        ->label('Company Name'),
                        TextEntry::make('display_name')
                        ->label('Display Name'),
                        TextEntry::make('business_type')
                        ->label('Business Type'),
                    ])
                    ->columns(2),

                Section::make('Contact Information')
                    ->schema([
                        TextEntry::make('email_contact_1')
                        ->label('Primary Contact Email')
                        ->copyable(),
                        TextEntry::make('email_contact_2')
                        ->label('Secondary Contact Email')
                        ->copyable(),
                        TextEntry::make('telephone_contact_1')
                        ->label('Primary Contact Phone')
                        ->copyable(),
                        TextEntry::make('telephone_contact_2')
                        ->label('Secondary Contact Phone')
                        ->copyable(),  
                    ])
                    ->columns(2),

                Section::make('Address Information')
                    ->schema([
                        TextEntry::make('region')
                        ->label('Region'),
                        TextEntry::make('province')
                        ->label('Province'),
                        TextEntry::make('city_municipality')
                        ->label('City/Municipality'),
                        TextEntry::make('barangay')
                        ->label('Barangay'),
                        TextEntry::make('street')
                        ->label('Street Address'),
                        TextEntry::make('zipcode')
                        ->label('Zip Code'),
                    ])
                    ->columns(3),

                Section::make('Business Registration')
                    ->schema([
                        TextEntry::make('business_registration_number')
                        ->label('Business Registration Number'),
                        TextEntry::make('tin_number')
                        ->label('Tax Identification Number'),
                    ])
                    ->columns(2),
            ]);
    }
}