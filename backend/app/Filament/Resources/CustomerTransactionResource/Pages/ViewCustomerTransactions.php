<?php

namespace App\Filament\Resources\CustomerTransactionResource\Pages;

use App\Filament\Resources\CustomerTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\ImageEntry;

class ViewCustomerTransactions extends ViewRecord
{
    protected static string $resource = CustomerTransactionResource::class;

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
                Section::make('Transaction Information')
                    ->schema([
                        TextEntry::make('transaction_id')
                            ->label('Transaction ID')
                            ->copyable(),
                        TextEntry::make('customer_email')
                            ->label('Customer Email')
                            ->copyable(),
                        TextEntry::make('company.company_name')
                            ->label('Company Name'),
                        TextEntry::make('loyaltyProgram.program_name')
                            ->label('Loyalty Program')
                            ->placeholder('N/A'),
                    ])
                    ->columns(2),

                Section::make('Transaction Details')
                    ->schema([
                        TextEntry::make('transaction_type')
                            ->label('Type')
                            ->badge()
                            ->color(fn (string $state): string => match ($state){
                                'earning' => 'success',
                                'redemption' => 'warning',
                                default => 'secondary',
                            }),
                        TextEntry::make('points_earned')
                            ->label('Points')
                            ->suffix('pts')
                            ->color(fn ($record) => $record->transaction_type === 'redemption' ? 'danger' : 'success'),
                        TextEntry::make('purchase_amount')
                            ->label('Purchase Amount')
                            ->money('PHP', true)
                            ->placeholder('N/A')
                            ->color(fn ($record) => $record->transaction_type === 'redemption' ? 'danger' : 'success'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'credited' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                'redeemed' => 'info',
                                default => 'secondary',
                            }),
                    ])
                    ->columns(2),

                    Section::make('QR Code')
                    ->schema([
                        ImageEntry::make('qr_code_path')
                            ->label('QR Code')
                            ->disk('public')
                            ->size(200)
                            ->visible(fn ($record) => !empty($record->qr_code_path))
                    ])
                    ->visible(fn ($record) => !empty($record->qr_code_path)),
            ]); 
    }
}