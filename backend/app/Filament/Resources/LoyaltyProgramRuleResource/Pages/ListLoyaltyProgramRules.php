<?php

namespace App\Filament\Resources\LoyaltyProgramRuleResource\Pages;

use App\Filament\Resources\LoyaltyProgramRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoyaltyProgramRules extends ListRecords
{
    protected static string $resource = LoyaltyProgramRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
