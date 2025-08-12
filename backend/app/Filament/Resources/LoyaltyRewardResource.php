<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyRewardResource\Pages;
use App\Filament\Resources\LoyaltyRewardResource\RelationManagers;
use App\Models\LoyaltyReward;
use App\Traits\HasRoleHelpers;
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
use Illuminate\Support\Facades\Auth;

class LoyaltyRewardResource extends Resource
{
    use HasRoleHelpers;
    
    protected static ?string $model = LoyaltyReward::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();
        
        // If user is superadmin, show all rewards
        if ($user->hasRole('superadmin')) {
            return $query;
        }
        
        // If user is handler, only show rewards from their companies' loyalty program rules
        if ($user->hasRole('handler')) {
            $companyIds = $user->getCompanyIds();
            return $query->whereHas('loyaltyProgramRule.loyaltyProgram', function (Builder $subQuery) use ($companyIds) {
                $subQuery->whereIn('company_id', $companyIds);
            });
        }
        
        // Default: show no rewards if role is not recognized
        return $query->whereRaw('1 = 0');
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasRole('superadmin') || $user->hasRole('handler'));
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        
        if (!$user) return false;
        
        // Superadmin can edit any reward
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Handler can edit rewards from their companies' programs
        if ($user->hasRole('handler')) {
            return $user->canAccessCompany($record->loyaltyProgramRule->loyaltyProgram->company_id);
        }
        
        return false;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        
        if (!$user) return false;
        
        // Superadmin can delete any reward
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Handler can delete rewards from their companies' programs
        if ($user->hasRole('handler')) {
            return $user->canAccessCompany($record->loyaltyProgramRule->loyaltyProgram->company_id);
        }
        
        return false;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('superadmin');
        
        return $form
            ->schema([
                Forms\Components\Section::make('Reward Details')->schema([
                    TextInput::make('reward_name')
                        ->required(),
                        
                    Select::make('loyalty_program_rule_id')
                        ->label('Loyalty Program Rule')
                        ->relationship('loyaltyProgramRule', 'rule_name', modifyQueryUsing: function (Builder $query) use ($user, $isSuperadmin) {
                            if ($isSuperadmin) {
                                return $query->where('is_active', true);
                            }
                            
                            if ($user->hasRole('handler')) {
                                $companyIds = $user->getCompanyIds();
                                return $query->whereHas('loyaltyProgram', function (Builder $subQuery) use ($companyIds) {
                                    $subQuery->whereIn('company_id', $companyIds)->where('is_active', true);
                                })->where('is_active', true);
                            }
                            
                            return $query->whereRaw('1 = 0');
                        })
                        ->searchable()
                        ->required()
                        ->preload(),
                        
                    Select::make('reward_type')
                        ->options([
                            'voucher' => 'Voucher',
                            'discount' => 'Discount'
                        ])
                        ->required(),
                        
                    TextInput::make('point_cost')
                        ->numeric()
                        ->required()
                        ->suffix('points'),
                ])->columns(2),

                Forms\Components\Section::make('Reward Configuration')->schema([
                    TextInput::make('discount_value')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->nullable()
                        ->prefix('PHP')
                        ->visible(fn(Forms\Get $get) => $get('reward_type') === 'discount'),
                        
                    TextInput::make('discount_precentage')
                        ->label('Discount Percentage')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue(100)
                        ->nullable()
                        ->suffix('%')
                        ->visible(fn(Forms\Get $get) => $get('reward_type') === 'discount'),
                        
                    TextInput::make('voucher_code')
                        ->nullable()
                        ->visible(fn(Forms\Get $get) => $get('reward_type') === 'voucher'),
                        
                    Toggle::make('is_active')
                        ->default(true)
                        ->required(),
                        
                    TextInput::make('max_redemption_rate')
                        ->label('Max Redemptions')
                        ->numeric()
                        ->nullable()
                        ->helperText('Leave empty for unlimited redemptions'),
                        
                    TextInput::make('expiration_days')
                        ->label('Expires After (Days)')
                        ->numeric()
                        ->nullable()
                        ->helperText('Leave empty for no expiration'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('superadmin');
        
        return $table
            ->columns([
                TextColumn::make('reward_name')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('loyaltyProgramRule.rule_name')
                    ->label('Program Rule')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('loyaltyProgramRule.loyaltyProgram.company.company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->visible($isSuperadmin),
                    
                TextColumn::make('reward_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'voucher' => 'success',
                        'discount' => 'primary',
                        default => 'gray',
                    }),
                    
                TextColumn::make('point_cost')
                    ->suffix(' pts')
                    ->numeric()
                    ->sortable(),
                    
                TextColumn::make('discount_value')
                    ->prefix('PHP')
                    ->numeric(2)
                    ->placeholder('N/A'),
                    
                TextColumn::make('discount_precentage')
                    ->label('Discount %')
                    ->suffix('%')
                    ->numeric(1)
                    ->placeholder('N/A'),
                    
                TextColumn::make('voucher_code')
                    ->placeholder('N/A')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                IconColumn::make('is_active')
                    ->boolean(),
                    
                TextColumn::make('max_redemption_rate')
                    ->label('Max Redemptions')
                    ->placeholder('Unlimited')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('expiration_days')
                    ->label('Expires (Days)')
                    ->placeholder('Never')
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company')
                    ->label('Company')
                    ->relationship('loyaltyProgramRule.loyaltyProgram.company', 'company_name')
                    ->preload()
                    ->visible($isSuperadmin),
                    
                SelectFilter::make('loyalty_program_rule_id')
                    ->label('Program Rule')
                    ->relationship('loyaltyProgramRule', 'rule_name', modifyQueryUsing: function (Builder $query) use ($user, $isSuperadmin) {
                        if ($isSuperadmin) {
                            return $query;
                        }
                        
                        if ($user->hasRole('handler')) {
                            $companyIds = $user->getCompanyIds();
                            return $query->whereHas('loyaltyProgram', function (Builder $subQuery) use ($companyIds) {
                                $subQuery->whereIn('company_id', $companyIds);
                            });
                        }
                        
                        return $query->whereRaw('1 = 0');
                    })
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('reward_type')
                    ->options([
                        'voucher' => 'Voucher',
                        'discount' => 'Discount'
                    ])
                    ->searchable()
                    ->preload(),
                    
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\Action::make('duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('secondary')
                    ->action(fn(LoyaltyReward $record) => static::duplicateRule($record))
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\Action::make('toggle')
                    ->icon(fn(LoyaltyReward $r) => $r->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn(LoyaltyReward $r) => $r->is_active ? 'warning' : 'success')
                    ->label(fn(LoyaltyReward $r) => $r->is_active ? 'Deactivate' : 'Activate')
                    ->action(fn(LoyaltyReward $r) => static::toggleStatus($r))
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => static::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible($isSuperadmin || $user->hasRole('handler')),
                Tables\Actions\BulkAction::make('Activate')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->action(fn(Collection $records) => $records->each->update(['is_active' => true])),
                Tables\Actions\BulkAction::make('Deactivate')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
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
        $copy->reward_name .= ' (Copy)';
        $copy->is_active = false;
        $copy->save();

        Notification::make()
            ->title('Reward duplicated successfully')
            ->success()
            ->send();
    }

    public static function toggleStatus(LoyaltyReward $record): void
    {
        $record->update(['is_active' => !$record->is_active]);

        Notification::make()
            ->title('Reward status updated')
            ->success()
            ->send();
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        
        if (!$user) return null;
        
        if ($user->isSuperAdmin()) {
            $total = static::getModel()::count();
            $active = static::getModel()::where('is_active', true)->count();
            return "{$active}/{$total}";
        }
        
        if ($user->isHandler()) {
            $companyIds = $user->getCompanyIds();
            return (string) static::getModel()::whereHas('loyaltyProgramRule.loyaltyProgram', function (Builder $query) use ($companyIds) {
                $query->whereIn('company_id', $companyIds);
            })->count();
        }
        
        return null;
    }

    public static function getNavigationLabel(): string
    {
        $user = Auth::user();
        
        if ($user && $user->isHandler()) {
            return 'My Rewards';
        }
        
        return 'Loyalty Rewards';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->isSuperAdmin() || $user->isHandler());
    }
}
