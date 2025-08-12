<?php

namespace App\Filament\Resources\CustomerTransactionResource\Pages;

use App\Filament\Resources\CustomerTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerTransaction extends EditRecord
{
    protected static string $resource = CustomerTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
