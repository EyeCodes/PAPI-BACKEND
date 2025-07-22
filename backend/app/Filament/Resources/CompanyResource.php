<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Helpers\GetLocationDataHelper;
use App\Models\Company;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Management';
    protected static ?string $label = 'Company';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Company Details')->schema([
                Forms\Components\TextInput::make('company_name')->required()->maxLength(255),
                Forms\Components\TextInput::make('display_name')->maxLength(255),
                Forms\Components\FileUpload::make('company_logo')
                    ->image()
                    ->imageCropAspectRatio('1:1')
                    ->maxSize(548),
                Forms\Components\Select::make('business_type')
                    ->required()
                    ->options(fn() => self::getBusinessTypes()->pluck('name', 'id'))
                    ->searchable(),
            ])->columns(2),

            Forms\Components\Section::make('Contacts')->schema([
                Forms\Components\TextInput::make('telephone_contact_1')->required()->tel(),
                Forms\Components\TextInput::make('telephone_contact_2')->tel(),
                Forms\Components\TextInput::make('email_contact_1')->required()->email(),
                Forms\Components\TextInput::make('email_contact_2')->email(),
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
                Forms\Components\TextInput::make('zipcode')->required()->numeric(),
                Forms\Components\TextInput::make('street')->required()->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('Company IDs')->schema([
                Forms\Components\TextInput::make('business_registration_number')->required()->maxLength(255),
                Forms\Components\TextInput::make('tin_number')->required()->numeric()->maxLength(255),
            ])->columns(2),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('business_type')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Owner')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_type')
                    ->options(fn() => self::$model::distinct()->pluck('business_type', 'business_type')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    protected static function getBusinessTypes(): Collection
    {
        try {
            return collect(json_decode(Storage::disk('local')->get('businessTypes.json'), true));
        } catch (\Throwable $e) {
            Log::error('BusinessTypes load failed: ' . $e->getMessage());
            return collect();
        }
    }
}
