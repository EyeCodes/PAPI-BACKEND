<?php

namespace App\Filament\Resources\TransactionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'product.name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->required()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        if ($state) {
                        $product = \App\Models\Product::find($state);
                            if ($product) {
                                $set('unit_price', $product->price);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                        $productId = $get('product_id');
                        if ($productId && $state) {
                            $product = \App\Models\Product::find($productId);
                            if ($product) {
                                $set('total_price', $product->price * $state);
                            }
                        }
                    }),
                Forms\Components\TextInput::make('unit_price')
                    ->numeric()
                    ->prefix('₱')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('total_price')
                    ->numeric()
                    ->prefix('₱')
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product.name')
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.price')
                    ->label('Unit Price')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Price')
                    ->getStateUsing(function ($record) {
                        return $record->product->price * $record->quantity;
                    })
                    ->money('PHP')
                    ->sortable()
                    ->color('success'),
                Tables\Columns\TextColumn::make('product.merchant.name')
                    ->label('Merchant')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->label('Product'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Calculate total price
                        if (isset($data['product_id']) && isset($data['quantity'])) {
                            $product = \App\Models\Product::find($data['product_id']);
                            if ($product) {
                                $data['total_price'] = $product->price * $data['quantity'];
                            }
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Calculate total price
                        if (isset($data['product_id']) && isset($data['quantity'])) {
                            $product = \App\Models\Product::find($data['product_id']);
                            if ($product) {
                                $data['total_price'] = $product->price * $data['quantity'];
                            }
                        }
                        return $data;
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
