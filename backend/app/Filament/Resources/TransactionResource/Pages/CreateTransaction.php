<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Services\PointsService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Ensure amount is set from calculated amount
        if (isset($data['calculated_amount'])) {
            $data['amount'] = $data['calculated_amount'];
        }

        // Remove calculated fields that shouldn't be saved
        unset($data['calculated_amount'], $data['calculated_points']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Create transaction items
        $formData = $this->form->getState();
        if (isset($formData['items']) && is_array($formData['items'])) {
            foreach ($formData['items'] as $item) {
                if (isset($item['product_id']) && isset($item['quantity'])) {
                    $record->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }
        }

        // Calculate and award points
        if ($record->user_id) {
            $pointsService = new PointsService();
            $pointsService->awardPoints($record);

            // Get the awarded points from the updated record
            $points = $record->fresh()->awarded_points ?? 0;

            Notification::make()
                ->title('Transaction Created')
                ->body("Transaction created successfully. {$points} points awarded to customer.")
                ->success()
                ->send();
        }
    }
}
