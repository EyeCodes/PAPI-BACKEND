<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerTransactionResource\Pages;
use App\Models\CustomerPoint;
use App\Models\Company;
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

class CustomerTransactionResource extends Resource
{
    protected static ?string $model = CustomerPoint::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Transaction Management';

    protected static ?string $navigationLabel = 'Transaction History';

    protected static ?string $modelLabel = 'Transaction';

    protected static ?string $pluralModelLabel = 'Transactions';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
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
                        ->relationship('company', 'company_name')
                        ->disabled(),
                    
                    Forms\Components\Select::make('loyalty_program_id')
                        ->label('Loyalty Program')
                        ->relationship('loyaltyProgram', 'program_name')
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
                        ->required(),
                    
                    Forms\Components\DateTimePicker::make('credited_at')
                        ->label('Credited At')
                        ->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
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
                    ->toggleable(),
                
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
                    ->relationship('company', 'company_name')
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
}
