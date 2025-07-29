<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers\MerchantPointsRelationManager;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Users';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignorable: fn($record) => $record)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->required(fn($operation) => $operation === 'create')
                            ->dehydrated(fn($state) => filled($state))
                            ->minLength(8),
                        Forms\Components\TextInput::make('firebase_uid')
                            ->label('Firebase UID')
                            ->maxLength(255)
                            ->helperText('Firebase authentication UID (optional)'),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Created At')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('updated_at')
                            ->label('Updated At')
                            ->disabled(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('merchant_points_count')
                    ->label('Merchant Points Records')
                    ->counts('merchantPoints')
                    ->sortable()
                    ->description('Number of merchants with points'),
                Tables\Columns\TextColumn::make('total_points')
                    ->label('Total Points')
                    ->getStateUsing(function (User $record): string {
                        $totalPoints = $record->merchantPoints()->sum('points');
                        return number_format($totalPoints) . ' points';
                    })
                    ->sortable()
                    ->description('Across all merchants'),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('email_verified')
                    ->form([
                        Forms\Components\Checkbox::make('verified')
                            ->label('Email Verified'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $data['verified']
                        ? $query->whereNotNull('email_verified_at')
                        : $query),
                Tables\Filters\Filter::make('has_points')
                    ->form([
                        Forms\Components\Checkbox::make('has_points')
                            ->label('Has Points'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder => $data['has_points']
                        ? $query->whereHas('merchantPoints', fn($q) => $q->where('points', '>', 0))
                        : $query),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            MerchantPointsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
