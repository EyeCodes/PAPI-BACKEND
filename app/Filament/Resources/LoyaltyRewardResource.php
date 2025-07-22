<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyRewardResource\Pages;
use App\Filament\Resources\LoyaltyRewardResource\RelationManagers;
use App\Models\LoyaltyReward;
use Dom\Text;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Collection;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use PHPUnit\Event\Code\Test;
use PHPUnit\Runner\DeprecationCollector\Collector;

class LoyaltyRewardResource extends Resource
{
    protected static ?string $model = LoyaltyReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('reward_name')->required(),
                Select::make('program_rules_id')
                ->relationship('loyaltyProgramRule', 'rule_name')
                ->searchable()
                ->required()
                ->preload(),
                Select::make('reward_type')->options(['voucher'=>'voucher','discount'=>'discount'])->required(),
                TextInput::make('point_cost')->numeric()->required(),
                TextInput::make('discount_value')->numeric()
                ->default(0)
                ->minValue(0)
                ->nullable(),
                TextInput::make('discount_precentage')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->maxValue(100)
                ->nullable(),
                TextInput::make('voucher_code')->nullable(),
                Toggle::make('is_active')->default(true)->required(),
                TextInput::make('max_redemption_rate')->numeric()->nullable(),
                TextInput::make('expiration_days')->numeric()->nullable(),


            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reward_name'),
                TextColumn::make('reward_type'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->date(),
            ])
            ->filters([
                SelectFilter::make('reward_type')->searchable()->preload()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')->icon('heroicon-o-document-duplicate')->color('secondary')
                    ->action(fn(LoyaltyReward $record) => static::duplicateRule($record)),
                Tables\Actions\Action::make('toggle')->icon(fn(LoyaltyReward $r) => $r->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn(LoyaltyReward $r) => $r->is_active ? 'warning' : 'success')
                    ->label(fn(LoyaltyReward $r) => $r->is_active ? 'Deactivate' : 'Activate')
                    ->action(fn(LoyaltyReward $r) => static::toggleStatus($r)),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLoyaltyRewards::route('/'),
            'create' => Pages\CreateLoyaltyReward::route('/create'),
            'edit' => Pages\EditLoyaltyReward::route('/{record}/edit'),
        ];
    }

   public static function duplicateRule(LoyaltyReward $record): void
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

    public static function toggleStatus(LoyaltyReward $record): void
    {
        $record->update(['is_active' => !$record->is_active]);

        Notification::make()
            ->title('Rule status updated')
            ->success()
            ->send();
    }


}
