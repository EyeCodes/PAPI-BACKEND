<?php

namespace App\Filament\Resources\MerchantResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpan(2),
                Forms\Components\FileUpload::make('image')
                    ->image()
                    ->directory('product-images')
                    ->maxSize(2048)
                    ->columnSpan(2),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('₱')
                    ->minValue(0)
                    ->step(0.01),
                Forms\Components\Select::make('currency')
                    ->options([
                        'PHP' => 'PHP'
                    ])
                    ->default('PHP')
                    ->required(),
                Forms\Components\TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
                Forms\Components\TextInput::make('external_id')
                    ->maxLength(255)
                    ->unique(),
                Forms\Components\TextInput::make('source')
                    ->maxLength(255),
                Forms\Components\KeyValue::make('metadata')
                    ->columnSpan(2),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->square()
                    ->defaultImageUrl(url('/images/placeholder.png')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('PHP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn(int $state): string => $state <= 0 ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('points_rules_count')
                    ->label('Points Rules')
                    ->counts('pointsRules')
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('currency')
                    ->options([
                        'PHP' => 'PHP',
                    ]),
                Tables\Filters\Filter::make('low_stock')
                    ->query(fn(Builder $query): Builder => $query->where('stock', '<=', 10))
                    ->label('Low Stock (≤ 10)'),
                Tables\Filters\Filter::make('has_points_rules')
                    ->query(fn(Builder $query): Builder => $query->has('pointsRules'))
                    ->label('Has Points Rules'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Product'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('manage_points_rules')
                    ->label('Points Rules')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->url(fn($record) => route('filament.admin.resources.products.edit', ['record' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->withTrashed());
    }
}
