<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Traits\HasRoleHelpers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;


class UserResource extends Resource
{
    use HasRoleHelpers;

    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User  Management';

    // Filament Shield handles permissions, so we only need data filtering
    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();

        // If user is superadmin, show all users
        if ($user && $user->hasRole('superadmin')) {
            return $query;
        }

        // If user is handler, only show users from their companies
        if ($user && $user->hasRole('handler')) {
            $companyIds = $user->companies->pluck('id');

            return $query->where(function (Builder $query) use ($user, $companyIds) {
                // Show themselves
                $query->where('id', $user->id)
                    // And users from their companies (but not other handlers unless they share companies)
                    ->orWhereHas('companies', function (Builder $subQuery) use ($companyIds) {
                        $subQuery->whereIn('companies.id', $companyIds);
                    });
            });
        }

        // Default: show no users if role is not recognized
        return $query->whereRaw('1 = 0');
    }

    /**
     * Check if current user can create users
     */
    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasRole('superadmin') || $user->hasRole('handler'));
    }

    /**
     * Check if current user can edit a specific user record
     */
    public static function canEdit($record): bool
    {
        $user = Auth::user();

        if (!$user) return false;

        // Superadmin can edit anyone
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Handler can edit users from their companies
        if ($user->hasRole('handler')) {
            // Can always edit themselves
            if ($record->id === $user->id) {
                return true;
            }

            // Check if the record user belongs to any of their companies
            $handlerCompanyIds = $user->companies->pluck('id');
            $recordCompanyIds = $record->companies->pluck('id');

            return $handlerCompanyIds->intersect($recordCompanyIds)->isNotEmpty();
        }

        return false;
    }

    /**
     * Check if current user can delete a specific user record
     */
    public static function canDelete($record): bool
    {
        $user = Auth::user();

        if (!$user) return false;

        // Users cannot delete themselves
        if ($record->id === $user->id) {
            return false;
        }

        // Superadmin can delete anyone (except themselves, checked above)
        if ($user->hasRole('superadmin')) {
            return true;
        }

        // Handler can delete users from their companies (but not other handlers)
        if ($user->hasRole('handler')) {
            // Cannot delete other handlers or superadmins
            if ($record->hasRole('handler') || $record->hasRole('superadmin')) {
                return false;
            }

            // Check if the record user belongs to any of their companies
            $handlerCompanyIds = $user->companies->pluck('id');
            $recordCompanyIds = $record->companies->pluck('id');

            return $handlerCompanyIds->intersect($recordCompanyIds)->isNotEmpty();
        }

        return false;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isCreating = $form->getOperation() === 'create';

        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->dehydrated(fn ($state) => filled($state))
                    ->minLength(8)
                    ->label('Password')
                    ->helperText($isCreating ? 'Minimum 8 characters required' : 'Leave empty to keep current password'),

                Forms\Components\Select::make('roles')
                    ->multiple()
                    ->relationship('roles', 'name', modifyQueryUsing: function (Builder $query) use ($user) {
                        // Superadmin can assign any role
                        if ($user->hasRole('superadmin')) {
                            return $query;
                        }

                        // Handler can only assign non-admin roles
                        if ($user->hasRole('handler')) {
                            return $query->whereNotIn('name', ['superadmin', 'handler']);
                        }

                        return $query->whereRaw('1 = 0');
                    })
                    ->preload()
                    ->visible(fn () => $user->hasRole('superadmin') || $user->hasRole('handler'))
                    ->helperText($user->hasRole('handler') ? 'You can only assign basic user roles' : null),

                Forms\Components\Select::make('companies')
                    ->multiple()
                    ->relationship('companies', 'company_name', modifyQueryUsing: function (Builder $query) use ($user) {
                        // Superadmin can assign any company
                        if ($user->hasRole('superadmin')) {
                            return $query->where('is_active', true);
                        }

                        // Handler can only assign their own companies
                        if ($user->hasRole('handler')) {
                            $companyIds = $user->companies->pluck('id');
                            return $query->whereIn('companies.id', $companyIds)->where('is_active', true);
                        }

                        return $query->whereRaw('1 = 0');
                    })
                    ->preload()
                    ->label('Companies')
                    ->helperText(function () use ($user) {
                        if ($user->hasRole('handler')) {
                            return 'You can only assign users to your companies';
                        }
                        return 'Select companies this user will have access to';
                    })
                    ->required(fn () => $user->hasRole('handler')), // Handlers must assign at least one company
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('roles.name')
                    ->badge()
                    ->colors([
                        'danger' => 'superadmin',
                        'warning' => 'handler',
                        'success' => fn ($state): bool => !in_array($state, ['superadmin', 'handler']),
                    ])
                    ->label('Roles'),

                Tables\Columns\TextColumn::make('companies.company_name')
                    ->badge()
                    ->label('Companies')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        $companies = $record->companies->pluck('company_name')->toArray();
                        return implode(', ', $companies);
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->label('Role'),

                Tables\Filters\SelectFilter::make('companies')
                    ->relationship('companies', 'company_name', modifyQueryUsing: function (Builder $query) use ($user) {
                        // Superadmin can filter by any company
                        if ($user->hasRole('superadmin')) {
                            return $query->where('is_active', true);
                        }

                        // Handler can only filter by their companies
                        if ($user->hasRole('handler')) {
                            $companyIds = $user->companies->pluck('id');
                            return $query->whereIn('companies.id', $companyIds)->where('is_active', true);
                        }

                        return $query->whereRaw('1 = 0');
                    })
                    ->label('Company'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => static::canDelete($record))
                    ->before(function ($record) {
                        // Additional safety check before deletion
                        if ($record->id === Auth::id()) {
                            Notification::make()
                                ->title('Cannot Delete')
                                ->body('You cannot delete your own account')
                                ->danger()
                                ->send();
                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records) {
                        // Prevent bulk deletion of current user
                        $currentUserId = Auth::id(); // Corrected variable name
                        foreach ($records as $record) {
                            if ($record->id === $currentUserId) { // Use the corrected variable name
                                Notification::make()
                                    ->title('Cannot Delete')
                                    ->body('You cannot delete your own account')
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $user = Auth::user();

        if ($user && $user->hasRole('superadmin')) {
            return static::getModel()::count();
        }

        if ($user && $user->hasRole('handler')) {
            $companyIds = $user->companies->pluck('id');
            return static::getModel()::whereHas('companies', function (Builder $query) use ($companyIds) {
                $query->whereIn('companies.id', $companyIds);
            })->count();
        }

        return null;
    }

    /**
     * Get the navigation label for the resource
     */
    public static function getNavigationLabel(): string
    {
        $user = Auth::user();

        if ($user && $user->hasRole('handler')) {
            return 'Team Members';
        }

        return 'Users';
    }
}
