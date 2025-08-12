<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Helpers\GetLocationDataHelper;
use App\Models\Company;
use Exception;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasRoleHelpers;
class CompanyResource extends Resource
{
    use HasRoleHelpers; 
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Company Management';
    protected static ?string $navigationLabel = 'Companies';
    protected static ?int $navigationSort = 1;

    // Filament Shield handles permissions, so we only need data filtering

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();
        
        // If user is superadmin, show all companies
        if ($user->hasRole('superadmin')) {
            return $query;
        }
        
        // If user is handler, only show companies they belong to
        if ($user->hasRole('handler')) {
            return $query->whereHas('users', function (Builder $query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }
        
        // Default: show no companies if role is not recognized
        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if current user can create companies
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('superadmin'); // Only superadmin can create companies
    }

    /**
     * Check if current user can edit a specific company record
     */
    public static function canEdit($record): bool
    {
        $user = Auth::user();
        
        if (!$user) return false;
        
        // Superadmin can edit any company
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Handler can edit companies they belong to (with restrictions)
        if ($user->hasRole('handler')) {
            return $user->companies->contains($record->id);
        }
        
        return false;
    }

    /**
     * Check if current user can delete a specific company record
     */
    public static function canDelete($record): bool
    {
        $user = Auth::user();
        
        if (!$user) return false;
        
        // Only superadmin can delete companies
        return $user->hasRole('superadmin');
    }

    public static function form(Forms\Form $form): Forms\Form
    {   
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('superadmin');
        $isHandler = $user->hasRole('handler');
        
        return $form->schema([
            Forms\Components\Section::make('Company Details')->schema([
                Forms\Components\TextInput::make('company_name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                    
                Forms\Components\TextInput::make('display_name')
                    ->maxLength(255)
                    ->helperText('Optional display name for the company'),
                
                Forms\Components\FileUpload::make('company_logo')
                    ->image()
                    ->imageCropAspectRatio('1:1')
                    ->maxSize(548)
                    ->directory('company-logos')
                    ->visibility('public'),
                    
                Forms\Components\Select::make('business_type_id')
                    ->required()
                    ->relationship('businessType', 'type')
                    ->preload()
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('type')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->visible($isSuperadmin), // Only superadmin can change business type
                    
                Forms\Components\Placeholder::make('business_type_display')
                    ->label('Business Type')
                    ->content(fn ($record) => $record?->businessType?->type ?? 'Not set')
                    ->visible($isHandler), // Handler sees read-only business type
                    
                Forms\Components\Select::make('user_id')
                    ->label('Employees/Users')
                    ->multiple()
                    ->preload()
                    ->relationship('users', 'name', modifyQueryUsing: function (Builder $query) use ($user, $isSuperadmin) {
                        if ($isSuperadmin) {
                            // Superadmin can assign any user
                            return $query;
                        }
                        
                        // Handler can only see users from their companies or unassigned users
                        if ($user->hasRole('handler')) {
                            return $query->where(function (Builder $subQuery) use ($user) {
                                $subQuery->whereHas('companies', function (Builder $companyQuery) use ($user) {
                                    $companyQuery->whereIn('companies.id', $user->companies->pluck('id'));
                                })
                                ->orWhereDoesntHave('companies'); // Include users without companies
                            });
                        }
                        
                        return $query->whereRaw('1 = 0');
                    })
                    ->searchable()
                    ->helperText($isHandler ? 'You can only assign users from your companies or unassigned users' : null),

                Forms\Components\Toggle::make('is_active')
                    ->label('Company Active')
                    ->default(true)
                    ->visible($isSuperadmin) // Only superadmin can activate/deactivate
                    ->helperText('Inactive companies will not appear in dropdowns'),
                    
                Forms\Components\Placeholder::make('status_display')
                    ->label('Status')
                    ->content(fn ($record) => $record?->is_active ? '✅ Active' : '❌ Inactive')
                    ->visible($isHandler), // Handler sees read-only status
            ])->columns(2),

            Forms\Components\Section::make('Contacts')->schema([
                Forms\Components\TextInput::make('telephone_contact_1')
                    ->label('Primary Phone')
                    ->required()
                    ->tel()
                    ->maxLength(20),
                    
                Forms\Components\TextInput::make('telephone_contact_2')
                    ->label('Secondary Phone')
                    ->tel()
                    ->maxLength(20),
                    
                Forms\Components\TextInput::make('email_contact_1')
                    ->label('Primary Email')
                    ->required()
                    ->email()
                    ->maxLength(255),
                    
                Forms\Components\TextInput::make('email_contact_2')
                    ->label('Secondary Email')
                    ->email()
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Address')->schema([
                Forms\Components\Select::make('region')
                    ->required()
                    ->options(GetLocationDataHelper::getRegions())
                    ->live()
                    ->afterStateUpdated(fn(Forms\Set $set) => collect(['province', 'city_municipality', 'barangay'])->each(fn($field) => $set($field, null))),
                    
                Forms\Components\Select::make('province')
                    ->required()
                    ->options(fn(Forms\Get $get) => GetLocationDataHelper::getProvinces($get('region')))
                    ->live()
                    ->afterStateUpdated(fn(Forms\Set $set) => collect(['city_municipality', 'barangay'])->each(fn($field) => $set($field, null))),
                    
                Forms\Components\Select::make('city_municipality')
                    ->required()
                    ->options(fn(Forms\Get $get) => GetLocationDataHelper::getMunicipalities($get('region'), $get('province')))
                    ->live()
                    ->afterStateUpdated(fn(Forms\Set $set) => $set('barangay', null)),
                    
                Forms\Components\Select::make('barangay')
                    ->required()
                    ->options(fn(Forms\Get $get) => GetLocationDataHelper::getBarangays($get('region'), $get('province'), $get('city_municipality')))
                    ->live(),
                    
                Forms\Components\TextInput::make('zipcode')
                    ->required()
                    ->numeric()
                    ->minValue(1000)
                    ->maxValue(9999)
                    ->length(4),
                    
                Forms\Components\TextInput::make('street')
                    ->required()
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Company Registration')->schema([
                Forms\Components\TextInput::make('business_registration_number')
                    ->label('Business Registration Number')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                    
                Forms\Components\TextInput::make('tin_number')
                    ->label('TIN Number')
                    ->required()
                    ->numeric()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ])->columns(2)
            ->visible($isSuperadmin), // Only superadmin can view/edit registration details

            Forms\Components\Section::make('Company Registration (Read Only)')
                ->schema([
                    Forms\Components\Placeholder::make('registration_display')
                        ->label('Business Registration Number')
                        ->content(fn ($record) => $record?->business_registration_number ?? 'Not set'),
                        
                    Forms\Components\Placeholder::make('tin_display')
                        ->label('TIN Number')
                        ->content(fn ($record) => $record?->tin_number ?? 'Not set'),
                ])
                ->columns(2)
                ->visible($isHandler), // Handler sees read-only registration details
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('superadmin');
        
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('company_logo')
                    ->label('Logo')
                    ->size(40)
                    ->circular(),
                    
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                    
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Display Name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('businessType.type')
                    ->label('Business Type')
                    ->sortable()
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Employees')
                    ->counts('users')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->visible($isSuperadmin),
                    
                Tables\Columns\TextColumn::make('email_contact_1')
                    ->label('Primary Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('telephone_contact_1')
                    ->label('Primary Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_type_id')
                    ->label('Business Type')
                    ->relationship('businessType', 'type'),
                    
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ])
                    ->visible($isSuperadmin),
                    
                Tables\Filters\Filter::make('has_users')
                    ->label('Has Employees')
                    ->query(fn (Builder $query): Builder => $query->has('users'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => static::canDelete($record))
                    ->before(function ($record) {
                        // Check if company has users
                        if ($record->users()->count() > 0) {
                            Notification::make()
                                ->title('Cannot Delete')
                                ->body('Company has employees assigned. Remove all employees first.')
                                ->danger()
                                ->send();
                            return false;
                        }
                        
                        // Check if company has loyalty programs
                        if ($record->loyaltyPrograms()->count() > 0) {
                            Notification::make()
                                ->title('Cannot Delete')
                                ->body('Company has loyalty programs. Remove all loyalty programs first.')
                                ->danger()
                                ->send();
                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->visible($isSuperadmin)
                    ->before(function ($records) {
                        foreach ($records as $record) {
                            if ($record->users()->count() > 0 || $record->loyaltyPrograms()->count() > 0) {
                                Notification::make()
                                    ->title('Cannot Delete')
                                    ->body('Some companies have employees or loyalty programs assigned.')
                                    ->danger()
                                    ->send();
                                return false;
                            }
                        }
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
            'view' => Pages\ViewCompany::route('/{record}'),
        ];
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
            return (string) $user->companies()->count();
        }
        
        return null;
    }

    /**
     * Get the navigation label for the resource
     */
    public static function getNavigationLabel(): string
    {
        $user = Auth::user();
        
        if ($user && $user->isHandler()) {
            return 'My Companies';
        }
        
        return 'Companies';
    }

    /**
     * Check if the current user can view this resource
     */
    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->isSuperAdmin() || $user->isHandler());
    }
}
