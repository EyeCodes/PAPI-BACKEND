<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers\TransactionItemsRelationManager;
use App\Filament\Resources\TransactionResource\Widgets\TransactionStatsWidget;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PointsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Transactions';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->options(User::whereHas('roles', function ($query) {
                                $query->where('name', 'customer');
                            })->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('calculated_points', 0);
                            }),
                        Forms\Components\Select::make('merchant_id')
                            ->label('Merchant')
                            ->options(Merchant::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set) {
                                $set('items', []);
                                $set('calculated_amount', 0);
                                $set('calculated_points', 0);
                            }),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Transaction Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Product')
                                    ->options(function (Get $get) {
                                        $merchantId = $get('../../merchant_id');
                                        if (!$merchantId) {
                                            return [];
                                        }
                                        return Product::where('merchant_id', $merchantId)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        if ($state) {
                                            $product = Product::find($state);
                                            if ($product) {
                                                $set('unit_price', $product->price);
                                                $set('total_price', $product->price * ($get('quantity') ?? 1));
                                            }
                                        }
                                        self::updateCalculations($get, $set);
                                    }),
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->default(1)
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                        $productId = $get('product_id');
                                        if ($productId && $state) {
                                            $product = Product::find($productId);
                                            if ($product) {
                                                $set('total_price', $product->price * $state);
                                            }
                                        }
                                        self::updateCalculations($get, $set);
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->numeric()
                                    ->prefix('₱')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('total_price')
                                    ->label('Total Price')
                                    ->numeric()
                                    ->prefix('₱')
                                    ->disabled()
                                    ->dehydrated(false),
                            ])
                            ->columns(4)
                            ->addActionLabel('Add Product')
                            ->itemLabel(
                                fn(array $state): ?string =>
                                Product::find($state['product_id'])?->name ?? 'Product'
                            )
                            ->reorderable(false)
                            ->collapsible()
                            ->collapsed()
                            ->defaultItems(0)
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                self::updateCalculations($get, $set);
                            }),
                    ]),

                Forms\Components\Section::make('Transaction Summary')
                    ->schema([
                        Forms\Components\TextInput::make('calculated_amount')
                            ->label('Total Amount')
                            ->numeric()
                            ->prefix('₱')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Automatically calculated from items'),
                        Forms\Components\TextInput::make('calculated_points')
                            ->label('Points to Award')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Calculated based on merchant points rules'),
                        Forms\Components\TextInput::make('amount')
                            ->label('Final Amount')
                            ->numeric()
                            ->prefix('₱')
                            ->required()
                            ->helperText('This will be the actual transaction amount'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Transaction $record): string => $record->user?->email ?? ''),
                Tables\Columns\TextColumn::make('merchant.name')
                    ->label('Merchant')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('PHP')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('awarded_points')
                    ->label('Points Awarded')
                    ->getStateUsing(function (Transaction $record): int {
                        if (!$record->user) return 0;

                        $pointsService = new PointsService();
                        return $pointsService->calculatePoints($record);
                    })
                    ->color('warning')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.points')
                    ->label('Customer Points')
                    ->getStateUsing(function (Transaction $record): int {
                        return $record->user?->points ?? 0;
                    })
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('merchant_id')
                    ->label('Merchant')
                    ->options(Merchant::pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Customer')
                    ->options(User::whereHas('roles', function ($query) {
                        $query->where('name', 'customer');
                    })->pluck('name', 'id')),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['to'], fn($q) => $q->whereDate('created_at', '<=', $data['to']));
                    }),
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min_amount')
                            ->label('Min Amount')
                            ->numeric()
                            ->prefix('₱'),
                        Forms\Components\TextInput::make('max_amount')
                            ->label('Max Amount')
                            ->numeric()
                            ->prefix('₱'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['min_amount'], fn($q) => $q->where('amount', '>=', $data['min_amount']))
                            ->when($data['max_amount'], fn($q) => $q->where('amount', '<=', $data['max_amount']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Transaction Details'),
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Transaction'),
                Tables\Actions\Action::make('recalculate_points')
                    ->label('Recalculate Points')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->action(function (Transaction $record) {
                        if ($record->user) {
                            $pointsService = new PointsService();
                            $oldPoints = $record->user->points;
                            $newPoints = $pointsService->calculatePoints($record);

                            // Remove old points and add new ones
                            $record->user->update(['points' => $oldPoints - $record->awarded_points + $newPoints]);
                            $record->update(['awarded_points' => $newPoints]);

                            Notification::make()
                                ->title('Points Recalculated')
                                ->body("Points recalculated: {$newPoints} points")
                                ->success()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Recalculate Points')
                    ->modalDescription('This will recalculate the points for this transaction based on current rules.')
                    ->modalSubmitActionLabel('Yes, recalculate'),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Transaction $record) {
                        // Remove points from user when transaction is deleted
                        if ($record->user && $record->awarded_points) {
                            $record->user->update([
                                'points' => $record->user->points - $record->awarded_points
                            ]);
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function ($records) {
                            // Remove points from users when transactions are deleted
                            foreach ($records as $record) {
                                if ($record->user && $record->awarded_points) {
                                    $record->user->update([
                                        'points' => $record->user->points - $record->awarded_points
                                    ]);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            TransactionItemsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            TransactionStatsWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    private static function updateCalculations(Get $get, Set $set): void
    {
        $items = $get('items') ?? [];
        $totalAmount = 0;

        foreach ($items as $item) {
            if (isset($item['product_id']) && isset($item['quantity'])) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $totalAmount += $product->price * $item['quantity'];
                }
            }
        }

        $set('calculated_amount', $totalAmount);

        // Calculate points if we have merchant and amount
        $merchantId = $get('merchant_id');
        $userId = $get('user_id');

        if ($merchantId && $totalAmount > 0) {
            // Create a temporary transaction for points calculation
            $tempTransaction = new Transaction([
                'merchant_id' => $merchantId,
                'user_id' => $userId,
                'amount' => $totalAmount,
            ]);

            $pointsService = new PointsService();
            $calculatedPoints = $pointsService->calculatePoints($tempTransaction);
            $set('calculated_points', $calculatedPoints);
        } else {
            $set('calculated_points', 0);
        }
    }
}
