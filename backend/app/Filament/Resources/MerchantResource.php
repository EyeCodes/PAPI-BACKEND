<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MerchantResource\Pages;
use App\Filament\Resources\MerchantResource\RelationManagers;
use App\Models\Merchant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Card;
use Illuminate\Database\Eloquent\Builder;

class MerchantResource extends Resource
{
    protected static ?string $model = Merchant::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationLabel = 'Merchants';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Card::make()
                    ->schema([
                        Section::make('General Information')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter merchant name')
                                    ->label('Merchant Name'),
                                Forms\Components\FileUpload::make('logo')
                                    ->image()
                                    ->directory('merchant-logos')
                                    ->previewable(true)
                                    ->maxSize(2048)
                                    ->label('Logo')
                                    ->helperText('Upload a logo (max 2MB, PNG/JPG)'),
                            ])
                            ->columns(2),
                        Section::make('Contact Information')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Forms\Components\Repeater::make('emails')
                                    ->label('Email Addresses')
                                    ->schema([
                                        Forms\Components\TextInput::make('value')
                                            ->label('Email')
                                            ->email()
                                            ->placeholder('e.g., contact@merchant.com'),
                                    ])
                                    ->addActionLabel('Add Email')
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['value'] ?? null)
                                    ->deleteAction(
                                        fn($action) => $action->requiresConfirmation()
                                    ),
                                Forms\Components\Repeater::make('phones')
                                    ->label('Phone Numbers')
                                    ->schema([
                                        Forms\Components\TextInput::make('value')
                                            ->label('Phone')
                                            ->tel()
                                            ->placeholder('e.g., +1 123-456-7890'),
                                    ])
                                    ->addActionLabel('Add Phone')
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['value'] ?? null)
                                    ->deleteAction(
                                        fn($action) => $action->requiresConfirmation()
                                    ),
                                Forms\Components\Repeater::make('addresses')
                                    ->label('Addresses')
                                    ->schema([
                                        Forms\Components\TextInput::make('value')
                                            ->label('Address')
                                            ->placeholder('e.g., 123 Main St, City, Country'),
                                    ])
                                    ->addActionLabel('Add Address')
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['value'] ?? null)
                                    ->deleteAction(
                                        fn($action) => $action->requiresConfirmation()
                                    ),
                                Forms\Components\Repeater::make('social_media')
                                    ->label('Social Media')
                                    ->schema([
                                        Forms\Components\TextInput::make('value')
                                            ->label('Social Media URL')
                                            ->url()
                                            ->placeholder('e.g., https://twitter.com/merchant'),
                                    ])
                                    ->addActionLabel('Add Social Media')
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['value'] ?? null)
                                    ->deleteAction(
                                        fn($action) => $action->requiresConfirmation()
                                    ),
                                Forms\Components\Repeater::make('website')
                                    ->label('Websites')
                                    ->schema([
                                        Forms\Components\TextInput::make('value')
                                            ->label('Website URL')
                                            ->url()
                                            ->placeholder('e.g., https://merchant.com'),
                                    ])
                                    ->addActionLabel('Add Website')
                                    ->collapsible()
                                    ->itemLabel(fn(array $state): ?string => $state['value'] ?? null)
                                    ->deleteAction(
                                        fn($action) => $action->requiresConfirmation()
                                    ),
                            ])
                            ->columns(1),
                        Section::make('Integration & Status')
                            ->collapsible()
                            ->collapsed()
                            ->schema([
                                Forms\Components\TextInput::make('external_id')
                                    ->label('External ID')
                                    ->maxLength(255)
                                    ->placeholder('Enter external ID for API integration')
                                    ->helperText('Used for syncing with external systems'),
                                Forms\Components\TextInput::make('source')
                                    ->label('Source')
                                    ->maxLength(255)
                                    ->placeholder('e.g., API, Manual')
                                    ->helperText('Origin of merchant data'),
                                Forms\Components\DateTimePicker::make('last_synced_at')
                                    ->label('Last Synced At')
                                    ->disabled()
                                    ->helperText('Automatically updated on API sync'),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->required()
                                    ->default(true)
                                    ->helperText('Enable or disable the merchant'),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Merchant Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state) => strtoupper($state))
                    ->wrap(),
                TextColumn::make('emails')
                    ->label('Emails')
                    ->getStateUsing(fn($record) => collect($record->emails)->pluck('value')->implode(', '))
                    ->wrap(),
                TextColumn::make('external_id')
                    ->label('External ID')
                    ->searchable()
                    ->toggleable(),
                BadgeColumn::make('source')
                    ->label('Source')
                    ->colors([
                        'primary' => 'API',
                        'secondary' => 'Manual',
                    ])
                    ->searchable(),
                TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ]),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
                Tables\Filters\Filter::make('last_synced_at')
                    ->form([
                        Forms\Components\DatePicker::make('synced_from')
                            ->label('Synced From'),
                        Forms\Components\DatePicker::make('synced_to')
                            ->label('Synced To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['synced_from'], fn($q) => $q->where('last_synced_at', '>=', $data['synced_from']))
                            ->when($data['synced_to'], fn($q) => $q->where('last_synced_at', '<=', $data['synced_to']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label('Toggle Active')
                    ->action(function (Merchant $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('warning')
                    ->requiresConfirmation(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(fn($records) => $records->toCsv())
                        ->color('success'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\PointsRulesRelationManager::class,
            // RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMerchants::route('/'),
            'create' => Pages\CreateMerchant::route('/create'),
            'view' => Pages\ViewMerchant::route('/{record}'),
            'edit' => Pages\EditMerchant::route('/{record}/edit'),
        ];
    }

    // public static function getWidgets(): array
    // {
    //     return [
    //         \App\Filament\Widgets\MerchantStatsWidget::class,
    //     ];
    // }
}
