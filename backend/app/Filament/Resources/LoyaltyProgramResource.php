<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyProgramResource\Pages;
use App\Filament\Resources\LoyaltyProgramResource\RelationManagers;
use App\Models\LoyaltyProgram;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Collection;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class LoyaltyProgramResource extends Resource
{
    protected static ?string $model = LoyaltyProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                TextInput::make('program_name')
                ->required()
                ->maxLength(255),

                Textarea::make('description')
                ->autosize()
                ->required(),

                TextInput::make('program_type')
                ->default('point based'),


                Select::make('company_id')->relationship('company', 'company_name')->preload()->searchable()->required(),


                Toggle::make('is_active')->default(true),
                DatePicker::make('start_date')->default(now())->required(),
                DatePicker::make('end_date')->afterOrEqual('start_date')->nullable(),
                Textarea::make('instructions')->autosize(),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('program_name'),
                TextColumn::make('program_type'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('created_at')->date(),
            ])
            ->filters([
                SelectFilter::make('program_type')->searchable()->preload()
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('duplicate')->icon('heroicon-o-document-duplicate')->color('secondary')
                    ->action(fn(LoyaltyProgram $record) => static::duplicateRule($record)),
                Tables\Actions\Action::make('toggle')->icon(fn(LoyaltyProgram $r) => $r->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn(LoyaltyProgram $r) => $r->is_active ? 'warning' : 'success')
                    ->label(fn(LoyaltyProgram $r) => $r->is_active ? 'Deactivate' : 'Activate')
                    ->action(fn(LoyaltyProgram $r) => static::toggleStatus($r)),
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
            'index' => Pages\ListLoyaltyPrograms::route('/'),
            'create' => Pages\CreateLoyaltyProgram::route('/create'),
            'edit' => Pages\EditLoyaltyProgram::route('/{record}/edit'),
        ];
    }

    public static function duplicateRule(LoyaltyProgram $record): void
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

    public static function toggleStatus(LoyaltyProgram $record): void
    {
        $record->update(['is_active' => !$record->is_active]);

        Notification::make()
            ->title('Rule status updated')
            ->success()
            ->send();
    }

}
