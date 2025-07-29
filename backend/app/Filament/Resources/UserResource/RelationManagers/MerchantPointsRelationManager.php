<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Services\PointsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class MerchantPointsRelationManager extends RelationManager
{
    protected static string $relationship = 'merchantPoints';
    protected static ?string $title = 'Merchant Points';
    protected static ?string $recordTitleAttribute = 'merchant.name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('merchant_id')
                    ->label('Merchant')
                    ->relationship('merchant', 'name')
                    ->searchable()
                    ->required()
                    ->disabled(fn($operation) => $operation === 'edit')
                    ->helperText('Merchant cannot be changed after creation.'),
                Forms\Components\TextInput::make('points')
                    ->label('Points Balance')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->helperText('Current points balance for this merchant.'),
                Forms\Components\TextInput::make('total_earned')
                    ->label('Total Earned')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->helperText('Total points earned from this merchant.'),
                Forms\Components\TextInput::make('total_spent')
                    ->label('Total Spent')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->helperText('Total points spent at this merchant.'),
                Forms\Components\DateTimePicker::make('last_earned_at')
                    ->label('Last Earned At')
                    ->disabled()
                    ->helperText('Automatically set when points are added.'),
                Forms\Components\DateTimePicker::make('last_spent_at')
                    ->label('Last Spent At')
                    ->disabled()
                    ->helperText('Automatically set when points are spent.'),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('merchant.name')
            ->columns([
                Tables\Columns\TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable()
                    ->description(fn($record) => $record->merchant->description ?? ''),
                Tables\Columns\TextColumn::make('points')
                    ->label('Current Balance')
                    ->formatStateUsing(fn($state) => number_format((int) $state) . ' points')
                    ->sortable()
                    ->color(fn($state) => (int) $state > 0 ? 'success' : 'gray')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('total_earned')
                    ->label('Total Earned')
                    ->formatStateUsing(fn($state) => number_format((int) $state) . ' points')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->formatStateUsing(fn($state) => number_format((int) $state) . ' points')
                    ->sortable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('last_earned_at')
                    ->label('Last Earned')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('last_spent_at')
                    ->label('Last Spent')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('merchant_id')
                    ->label('Merchant')
                    ->relationship('merchant', 'name'),
                Tables\Filters\Filter::make('has_points')
                    ->form([
                        Forms\Components\Checkbox::make('has_points')
                            ->label('Has Points Balance'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $data['has_points']
                        ? $query->where('points', '>', 0)
                        : $query),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Merchant Points Record')
                    ->modalHeading('Create Merchant Points Record')
                    ->modalDescription('Add a new merchant points record for this user.')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Merchant points record created')
                            ->body('The merchant points record has been created successfully.')
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('add_points')
                    ->label('Add Points')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label('Points to Add')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Enter the number of points to add to the balance.'),
                    ])
                    ->action(function (array $data, $record) {
                        $pointsService = new PointsService();
                        $pointsService->addPointsToUser($record->user_id, $record->merchant_id, (int) $data['points']);

                        Notification::make()
                            ->success()
                            ->title('Points added successfully')
                            ->body(number_format($data['points']) . ' points have been added to the balance.');
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Add Points')
                    ->modalDescription('Add points to the user\'s balance for this merchant.')
                    ->modalSubmitActionLabel('Add Points'),
                Tables\Actions\Action::make('spend_points')
                    ->label('Spend Points')
                    ->icon('heroicon-o-minus')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('points')
                            ->label('Points to Spend')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(fn($record) => $record->points)
                            ->required()
                            ->helperText('Enter the number of points to spend from the balance.'),
                    ])
                    ->action(function (array $data, $record) {
                        $pointsService = new PointsService();
                        $success = $pointsService->spendPointsFromUser($record->user_id, $record->merchant_id, (int) $data['points']);

                        if ($success) {
                            Notification::make()
                                ->success()
                                ->title('Points spent successfully')
                                ->body(number_format($data['points']) . ' points have been spent from the balance.');
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Failed to spend points')
                                ->body('Insufficient points balance or other error occurred.');
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Spend Points')
                    ->modalDescription('Spend points from the user\'s balance for this merchant.')
                    ->modalSubmitActionLabel('Spend Points'),
                Tables\Actions\ViewAction::make()
                    ->modalHeading('View Merchant Points Details'),
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Merchant Points Record'),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Delete Merchant Points Record')
                    ->modalDescription('Are you sure you want to delete this merchant points record? This action cannot be undone.')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Merchant points record deleted')
                            ->body('The merchant points record has been deleted successfully.')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->modalHeading('Delete Selected Records')
                        ->modalDescription('Are you sure you want to delete the selected merchant points records? This action cannot be undone.')
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->title('Records deleted')
                                ->body('The selected merchant points records have been deleted successfully.')
                        ),
                ]),
            ])
            ->defaultSort('points', 'desc')
            ->paginated([10, 25, 50, 100]);
    }
}
