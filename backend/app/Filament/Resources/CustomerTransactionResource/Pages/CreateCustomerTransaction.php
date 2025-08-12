<?php

namespace App\Filament\Resources\CustomerTransactionResource\Pages;

use App\Filament\Resources\CustomerTransactionResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerTransaction extends CreateRecord
{
    protected static string $resource = CustomerTransactionResource::class;
}
