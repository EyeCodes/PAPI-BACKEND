<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerTransactionResource\Pages;
use App\Models\CustomerPoint;
use App\Models\Company;
use App\Traits\HasRoleHelpers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

class CustomerTransactionResource extends Resource
{
    use HasRoleHelpers;
    
    protected static ?string $model = CustomerPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Transaction Management';

    protected static ?string $navigationLabel = 'Transaction History';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?string $pluralModelLabel = 'Transactions';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();
        
        // If user is superadmin, show all transactions
        if ($user->hasRole('superadmin')) {
            return $query;
        }
        
        // If user is handler, only show transactions from their companies
        if ($user->hasRole('handler')) {
            $companyIds = $user->getCompanyIds();
            return $query->whereIn('company_id', $companyIds);
        }
        
        // Default: show no transactions if role is not recognized
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
        
        // Superadmin can edit any transaction
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Handler can edit transactions from their companies
        if ($user->hasRole('handler')) {
            return $user->canAccessCompany($record->company_id);
        }
        
        return false;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        
        if (!$user) return false;
        
        // Only superadmin can delete transactions (for data integrity)
        return $user->hasRole('superadmin');
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('superadmin');
        
        return $form->schema([
            Forms\Components\Section::make('Transaction Details')
                ->schema([
                    Forms\Components\TextInput::make('transaction_id')
                        ->label('Transaction ID')
                        ->disabled(),
                    
                    Forms\Components\TextInput::make('customer_email')
                        ->label('Customer Email')
                        ->disabled(),
                    
                    Forms\Components\Select::make('company_id')
                        ->label('Company')
                        ->relationship('company', 'company_name', modifyQueryUsing: function (Builder $query) use ($user, $isSuperadmin) {
                            if ($isSuperadmin) {
                                return $query->where('is_active', true);
                            }
                            
                            if ($user->hasRole('handler')) {
                                $companyIds = $user->getCompanyIds();
                                return $query->whereIn('id', $companyIds)->where('is_active', true);
                            }
                            
                            return $query->whereRaw('1 = 0');
                        })
                        ->disabled(),
                    
                    Forms\Components\Select::make('loyalty_program_id')
                        ->label('Loyalty Program')
                        ->relationship('loyaltyProgram', 'program_name', modifyQueryUsing: function (Builder $query) use ($user, $isSuperadmin) {
                            if ($isSuperadmin) {
                                return $query->where('is_active', true);
                            }
                            
                            if ($user->hasRole('handler')) {
                                $companyIds = $user->getCompanyIds();
                                return $query->whereIn('company_id', $companyIds)->where('is_active', true);
                            }
                            
                            return $query->whereRaw('1 = 0');
                        })
                        ->disabled(),
                ])
                ->columns(2),

            Forms\Components\Section::make('Transaction Info')
                ->schema([
                    Forms\Components\TextInput::make('points_earned')
                        ->label('Points')
                        ->suffix('pts')
                        ->disabled(),
                    
                    Forms\Components\TextInput::make('purchase_amount')
                        ->label('Purchase Amount')
                        ->prefix('PHP')
                        ->disabled(),
                    
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'credited' => 'Credited',
                            'redeemed' => 'Redeemed',
                            'expired' => 'Expired',
                        ])
                        ->required()
                        ->disabled(fn ($record) => !static::canEdit($record)),
                    
                    Forms\Components\DateTimePicker::make('credited_at')
                        ->label('Credited At')
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('superadmin');
        
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('customer_email')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('company.company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->visible($isSuperadmin),
                
                Tables\Columns\TextColumn::make('loyaltyProgram.program_name')
                    ->label('Program')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('points_earned')
                    ->label('Points')
                    ->suffix(' pts')
                    ->alignCenter()
                    ->sortable()
                    ->color(fn ($record) => $record->transaction_type === 'redemption' ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('purchase_amount')
                    ->label('Amount')
                    ->prefix('PHP ')
                    ->alignCenter()
                    ->sortable()
                    ->placeholder('N/A'),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'credited' => 'success',
                        'redeemed' => 'info',
                        'expired' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y g:i A')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'company_name', modifyQueryUsing: function (Builder $query) use ($user, $isSuperadmin) {
                        if ($isSuperadmin) {
                            return $query->where('is_active', true);
                        }
                        
                        if ($user->hasRole('handler')) {
                            $companyIds = $user->getCompanyIds();
                            return $query->whereIn('id', $companyIds)->where('is_active', true);
                        }
                        
                        return $query->whereRaw('1 = 0');
                    })
                    ->preload()
                    ->visible($isSuperadmin), // Only show for superadmin
                
                SelectFilter::make('loyalty_program_id')
                    ->label('Loyalty Program')
                    ->relationship('loyaltyProgram', 'program_name', modifyQueryUsing: function (Builder $query) use ($user, $isSuperadmin) {
                        if ($isSuperadmin) {
                            return $query->where('is_active', true);
                        }
                        
                        if ($user->hasRole('handler')) {
                            $companyIds = $user->getCompanyIds();
                            return $query->whereIn('company_id', $companyIds)->where('is_active', true);
                        }
                        
                        return $query->whereRaw('1 = 0');
                    })
                    ->preload(),
                
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'credited' => 'Credited',
                        'redeemed' => 'Redeemed',
                        'expired' => 'Expired',
                    ]),
                
                Filter::make('customer_email')
                    ->form([
                        Forms\Components\TextInput::make('email')
                            ->label('Customer Email')
                            ->email(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['email'],
                            fn (Builder $query, $email): Builder => $query->where('customer_email', 'like', "%{$email}%"),
                        );
                    }),
                    
                Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => static::canDelete($record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible($isSuperadmin),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerTransactions::route('/'),
            'create' => Pages\CreateCustomerTransaction::route('/create'),
            'view' => Pages\ViewCustomerTransactions::route('/{record}'),
            'edit' => Pages\EditCustomerTransaction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();
        
        if (!$user) return null;
        
        if ($user->isSuperAdmin()) {
            $total = static::getModel()::count();
            $pending = static::getModel()::where('status', 'pending')->count();
            return $pending > 0 ? "{$pending} pending" : (string) $total;
        }
        
        if ($user->isHandler()) {
            $companyIds = $user->getCompanyIds();
            $total = static::getModel()::whereIn('company_id', $companyIds)->count();
            $pending = static::getModel()::whereIn('company_id', $companyIds)->where('status', 'pending')->count();
            return $pending > 0 ? "{$pending} pending" : (string) $total;
        }
        
        return null;
    }

    public static function getNavigationLabel(): string
    {
        $user = Auth::user();
        
        if ($user && $user->isHandler()) {
            return 'My Transactions';
        }
        
        return 'Transaction History';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->isSuperAdmin() || $user->isHandler());
    }
}
