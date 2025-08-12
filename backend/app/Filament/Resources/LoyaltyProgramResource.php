<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LoyaltyProgramResource\Pages;
use App\Filament\Resources\LoyaltyProgramResource\RelationManagers;
use App\Models\LoyaltyProgram;
use App\Traits\HasRoleHelpers;
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
use Illuminate\Support\Facades\Auth;

class LoyaltyProgramResource extends Resource
{
    use HasRoleHelpers;
    
    protected static ?string $model = LoyaltyProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Loyalty Management';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        $query = parent::getEloquentQuery();
        
        // If user is superadmin, show all loyalty programs
        if ($user->hasRole('superadmin')) {
            return $query;
        }
        
        // If user is handler, only show loyalty programs from their companies
        if ($user->hasRole('handler')) {
            $companyIds = $user->getCompanyIds();
            return $query->whereIn('company_id', $companyIds);
        }
        
        // Default: show no programs if role is not recognized
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
        
        // Superadmin can edit any loyalty program
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Handler can edit programs from their companies
        if ($user->hasRole('handler')) {
            return $user->canAccessCompany($record->company_id);
        }
        
        return false;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        
        if (!$user) return false;
        
        // Superadmin can delete any loyalty program
        if ($user->hasRole('superadmin')) {
            return true;
        }
        
        // Handler can delete programs from their companies
        if ($user->hasRole('handler')) {
            return $user->canAccessCompany($record->company_id);
        }
        
        return false;
    }

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('superadmin');
        $isHandler = $user->hasRole('handler');
        
        return $form
            ->schema([
                Forms\Components\Section::make('Program Details')->schema([
                    TextInput::make('program_name')
                        ->required()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->autosize()
                        ->required(),

                    TextInput::make('program_type')
                        ->default('point based'),

                    Select::make('company_id')
                        ->label('Company')
                        ->relationship('company', 'company_name', modifyQueryUsing: function (Builder $query) use ($user, $isSuperadmin) {
                            if ($isSuperadmin) {
                                return $query->where('is_active', true);
                            }
                            
                            if ($user->hasRole('handler')) {
                                $companyIds = $user->getCompanyIds();
                                return $query->whereIn('companies.id', $companyIds)->where('is_active', true);
                            }
                            
                            return $query->whereRaw('1 = 0');
                        })
                        ->preload()
                        ->searchable()
                        ->required()
                        ->default(function () use ($user) {
                            // Set default company for handlers with single company
                            if ($user->hasRole('handler') && $user->companies()->count() === 1) {
                                return $user->companies()->first()->id;
                            }
                            return null;
                        })
                        ->dehydrated()
                        ->hidden(function () use ($user) {
                            return $user->hasRole('handler') && $user->companies()->count() === 1;
                        }),

                    Forms\Components\Hidden::make('company_id')
                        ->default(function () use ($user) {
                            if ($user->hasRole('handler') && $user->companies()->count() === 1) {
                                return $user->companies()->first()->id;
                            }
                            return null;
                        })
                        ->visible(function () use ($user) {
                            return $user->hasRole('handler') && $user->companies()->count() === 1;
                        }),

                    Toggle::make('is_active')->default(true),
                    
                    DatePicker::make('start_date')->default(now())->required(),
                    
                    DatePicker::make('end_date')->afterOrEqual('start_date')->nullable(),
                    
                    Textarea::make('instructions')->autosize(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();
        $isSuperadmin = $user->hasRole('superadmin');
        
        return $table
            ->columns([
                TextColumn::make('program_name')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('company.company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->visible($isSuperadmin),
                    
                TextColumn::make('program_type')
                    ->badge(),
                    
                IconColumn::make('is_active')
                    ->boolean(),
                    
                TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->placeholder('No end date'),
                    
                TextColumn::make('created_at')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'company_name')
                    ->preload()
                    ->visible($isSuperadmin),
                    
                SelectFilter::make('program_type')
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
                    ->action(fn(LoyaltyProgram $record) => static::duplicateRule($record))
                    ->visible(fn ($record) => static::canEdit($record)),
                Tables\Actions\Action::make('toggle')
                    ->icon(fn(LoyaltyProgram $r) => $r->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn(LoyaltyProgram $r) => $r->is_active ? 'warning' : 'success')
                    ->label(fn(LoyaltyProgram $r) => $r->is_active ? 'Deactivate' : 'Activate')
                    ->action(fn(LoyaltyProgram $r) => static::toggleStatus($r))
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
            'index' => Pages\ListLoyaltyPrograms::route('/'),
            'create' => Pages\CreateLoyaltyProgram::route('/create'),
            'edit' => Pages\EditLoyaltyProgram::route('/{record}/edit'),
        ];
    }

    public static function duplicateRule(LoyaltyProgram $record): void
    {
        $copy = $record->replicate(['created_at', 'updated_at']);
        $copy->program_name .= ' (Copy)';
        $copy->is_active = false;
        $copy->save();

        Notification::make()
            ->title('Program duplicated successfully')
            ->success()
            ->send();
    }

    public static function toggleStatus(LoyaltyProgram $record): void
    {
        $record->update(['is_active' => !$record->is_active]);

        Notification::make()
            ->title('Program status updated')
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
            return (string) static::getModel()::whereIn('company_id', $companyIds)->count();
        }
        
        return null;
    }

    public static function getNavigationLabel(): string
    {
        $user = Auth::user();
        
        if ($user && $user->isHandler()) {
            return 'My Programs';
        }
        
        return 'Loyalty Programs';
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        return $user && ($user->isSuperAdmin() || $user->isHandler());
    }
}
