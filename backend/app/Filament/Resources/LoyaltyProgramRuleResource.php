<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyProgramRuleResource\Pages;
use App\Models\LoyaltyProgramRule;
use Filament\Forms;
use Filament\Tables;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class LoyaltyProgramRuleResource extends Resource
{
    protected static ?string $model = LoyaltyProgramRule::class;
    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?string $recordTitleAttribute = 'rule_name';
    protected static ?int $navigationSort = 2;

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Info')->schema([
                Forms\Components\Select::make('loyalty_program_id')
                    ->label('Loyalty Program')
                    ->relationship('loyaltyProgram', 'program_name')
                    ->required()
                    ->searchable()
                    ->preload(),

                Forms\Components\TextInput::make('rule_name')
                    ->required()
                    ->maxLength(255)
                    ->live()
                    ->afterStateUpdated(fn($state, Forms\Set $set) => $set('rule_name', ucfirst($state))),

                Forms\Components\Select::make('rule_type')
                    ->required()
                    ->options([
                        'purchase_based' => 'Purchase Based',
                        'birthday' => 'Birthday',
                        'referral_bonus' => 'Referral Bonus',
                    ])
                    ->live(),
            ])->columns(2),

            Forms\Components\Section::make('Points Configuration')->schema([
                Forms\Components\TextInput::make('points_earned')
                    ->numeric()->minValue(0)->required()
                    ->suffix('points')
                    ->helperText('Points rewarded for this rule.'),

                Forms\Components\TextInput::make('amount_per_point')
                    ->numeric()->step(0.01)->prefix('PHP')
                    ->visible(fn(Forms\Get $get) => $get('rule_type') === 'purchase_based')
                    ->helperText('Amount per point earned.'),

                Forms\Components\TextInput::make('min_purchase_amount')
                    ->numeric()->step(0.01)->prefix('PHP')
                    ->visible(fn(Forms\Get $get) => $get('rule_type') === 'purchase_based')
                    ->helperText('Minimum purchase to qualify.'),
            ])->columns(3),

            Forms\Components\Section::make('Product Config')->schema([
                Forms\Components\Select::make('product_category_id')
                    ->relationship('productCategory', 'category_name')
                    ->searchable()->nullable(),

                Forms\Components\Select::make('product_item_id')
                    ->relationship('productItem', 'item_name')
                    ->searchable()->nullable(),
            ])->columns(2)
                ->visible(fn(Forms\Get $get) => $get('rule_type') === 'purchase_based'),

            Forms\Components\Section::make('Status & Schedule')->schema([
                Forms\Components\Toggle::make('is_active')->default(true),
                Forms\Components\DatePicker::make('active_from_date')->default(now())->nullable(),
                Forms\Components\DatePicker::make('active_to_date')->afterOrEqual('active_from_date')->nullable(),
                Forms\Components\TextInput::make('usage_limit')
                    ->numeric()->minValue(1)->nullable()
                    ->helperText('Optional max usage.'),
            ])->columns(2),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('loyaltyProgram.program_name')->label('Program')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('rule_name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('rule_type')
                ->label('Rule Type')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'purchase_based' => 'primary',
                    'birthday' => 'success',
                    'referral_bonus' => 'warning',
                    default => 'gray',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'purchase_based' => 'Purchase Based',
                    'birthday' => 'Birthday Bonus',
                    'referral_bonus' => 'Referral Bonus',
                    default => ucfirst(str_replace('_', ' ', $state)),
                })
                ->sortable(),
            Tables\Columns\TextColumn::make('points_earned')->suffix(' pts')->numeric()->sortable(),
            Tables\Columns\TextColumn::make('amount_per_point')->prefix('PHP')->numeric(2)->placeholder('N/A'),
            Tables\Columns\TextColumn::make('min_purchase_amount')->prefix('PHP')->numeric(2)->placeholder('N/A'),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\TextColumn::make('active_from_date')->date('M d, Y')->placeholder('N/A'),
            Tables\Columns\TextColumn::make('active_to_date')->date('M d, Y')->placeholder('N/A'),
        ])
            ->filters([
                Tables\Filters\SelectFilter::make('loyalty_program_id')->relationship('loyaltyProgram', 'program_name')->searchable(),
                Tables\Filters\SelectFilter::make('rule_type')->options([
                    'purchase_based' => 'Purchase Based',
                    'birthday' => 'Birthday',
                    'referral_bonus' => 'Referral Bonus',
                ]),
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')->icon('heroicon-o-document-duplicate')->color('secondary')
                    ->action(fn(LoyaltyProgramRule $record) => static::duplicateRule($record)),
                Tables\Actions\Action::make('toggle')->icon(fn(LoyaltyProgramRule $r) => $r->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn(LoyaltyProgramRule $r) => $r->is_active ? 'warning' : 'success')
                    ->label(fn(LoyaltyProgramRule $r) => $r->is_active ? 'Deactivate' : 'Activate')
                    ->action(fn(LoyaltyProgramRule $r) => static::toggleStatus($r)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\BulkAction::make('Activate')->icon('heroicon-o-play')->color('success')
                    ->action(fn(Collection $records) => $records->each->update(['is_active' => true])),
                Tables\Actions\BulkAction::make('Deactivate')->icon('heroicon-o-pause')->color('warning')
                    ->action(fn(Collection $records) => $records->each->update(['is_active' => false])),
            ])
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyProgramRules::route('/'),
            'create' => Pages\CreateLoyaltyProgramRule::route('/create'),
            'edit' => Pages\EditLoyaltyProgramRule::route('/{record}/edit'),
        ];
    }

    public static function duplicateRule(LoyaltyProgramRule $record): void
    {
        $copy = $record->replicate(['created_at', 'updated_at']);
        $copy->rule_name .= ' (Copy)';
        $copy->is_active = false;
        $copy->save();

        Notification::make()
            ->title('Rule duplicated successfully')
            ->success()
            ->send();
    }

    public static function toggleStatus(LoyaltyProgramRule $record): void
    {
        $record->update(['is_active' => !$record->is_active]);

        Notification::make()
            ->title('Rule status updated')
            ->success()
            ->send();
    }
}
