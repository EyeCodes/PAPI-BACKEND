<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Services\PointsService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTransaction extends EditRecord
{
    protected static string $resource = TransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function () {
                    // Remove points from user when transaction is deleted
                    if ($this->record->user && $this->record->awarded_points) {
                        $this->record->user->update([
                            'points' => $this->record->user->points - $this->record->awarded_points
                        ]);
                    }
                }),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure amount is set from calculated amount
        if (isset($data['calculated_amount'])) {
            $data['amount'] = $data['calculated_amount'];
        }

        // Remove calculated fields that shouldn't be saved
        unset($data['calculated_amount'], $data['calculated_points']);

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // Get the old awarded points before updating
        $oldAwardedPoints = $record->getOriginal('awarded_points') ?? 0;

        // Update transaction items
        $formData = $this->form->getState();
        if (isset($formData['items']) && is_array($formData['items'])) {
            // Delete existing items
            $record->items()->delete();

            // Create new items
            foreach ($formData['items'] as $item) {
                if (isset($item['product_id']) && isset($item['quantity'])) {
                    $record->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }
        }

        // Recalculate and update points
        if ($record->user_id) {
            $pointsService = new PointsService();
            $newPoints = $pointsService->calculatePoints($record);

            // Update the transaction with new awarded points
            $record->update(['awarded_points' => $newPoints]);

            // Update user points (remove old points, add new points)
            $record->user->update([
                'points' => $record->user->points - $oldAwardedPoints + $newPoints
            ]);

            Notification::make()
                ->title('Transaction Updated')
                ->body("Transaction updated successfully. Points recalculated: {$newPoints} points")
                ->success()
                ->send();
        }
    }
}
