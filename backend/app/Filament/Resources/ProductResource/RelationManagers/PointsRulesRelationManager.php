<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enums\PointsRuleType;
use App\Services\FormulaParser;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class PointsRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'pointsRules';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Rule Type')
                    ->options(collect(PointsRuleType::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()]))
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        $set('parameters', []);
                        $set('conditions', []);
                    }),
                Forms\Components\Section::make('Parameters')
                    ->schema([
                        Forms\Components\TextInput::make('parameters.points')
                            ->label('Points')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->visible(fn($get) => in_array($get('type'), [
                                PointsRuleType::Fixed->value,
                                PointsRuleType::Threshold->value,
                                PointsRuleType::FirstPurchase->value,
                                PointsRuleType::LimitedTime->value,
                            ])),
                        Forms\Components\TextInput::make('parameters.divisor')
                            ->label('Divisor')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->visible(fn($get) => in_array($get('type'), [
                                PointsRuleType::Dynamic->value,
                                PointsRuleType::Combo->value,
                            ])),
                        Forms\Components\TextInput::make('parameters.multiplier')
                            ->label('Multiplier')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->visible(fn($get) => in_array($get('type'), [
                                PointsRuleType::Dynamic->value,
                                PointsRuleType::Combo->value,
                            ])),
                        Forms\Components\TextInput::make('parameters.amount_multiplier')
                            ->label('Amount Multiplier')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->visible(fn($get) => $get('type') === PointsRuleType::Combo->value),
                        Forms\Components\TextInput::make('parameters.quantity_multiplier')
                            ->label('Quantity Multiplier')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->visible(fn($get) => $get('type') === PointsRuleType::Combo->value),
                        Forms\Components\TextInput::make('parameters.formula')
                            ->label('Formula')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('e.g., floor(total / 100) * 2 + quantity * 5')
                            ->helperText('Use variables "total" (transaction amount) and "quantity" (item count). Supported functions: floor, ceil, round, min, max, +, -, *, /.')
                            ->visible(fn($get) => $get('type') === PointsRuleType::CustomFormula->value)
                            ->rules([
                                'regex:/^[a-zA-Z0-9\s\(\)\+\-\*\/\.]+$/',
                                function ($attribute, $value, $fail) {
                                    try {
                                        $parser = app(FormulaParser::class);
                                        $parser->evaluate($value, ['total' => 100, 'quantity' => 1]);
                                    } catch (\Throwable $e) {
                                        $fail('Invalid formula: ' . $e->getMessage());
                                    }
                                },
                            ]),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Conditions')
                    ->schema([
                        Forms\Components\TextInput::make('conditions.min_amount')
                            ->label('Minimum Amount')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->visible(fn($get) => $get('type') === PointsRuleType::Threshold->value),
                        Forms\Components\DateTimePicker::make('conditions.start_date')
                            ->label('Start Date')
                            ->required()
                            ->visible(fn($get) => $get('type') === PointsRuleType::LimitedTime->value),
                        Forms\Components\DateTimePicker::make('conditions.end_date')
                            ->label('End Date')
                            ->required()
                            ->visible(fn($get) => $get('type') === PointsRuleType::LimitedTime->value)
                            ->afterOrEqual('conditions.start_date'),
                    ])
                    ->columns(2),
                Forms\Components\TextInput::make('priority')
                    ->label('Priority')
                    ->numeric()
                    ->minValue(0)
                    ->default(10)
                    ->required()
                    ->helperText('Higher numbers indicate higher priority.'),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Rule Type')
                    ->formatStateUsing(fn($state) => $state->getLabel())
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('parameters')
                    ->label('Parameters')
                    ->formatStateUsing(fn($state) => json_encode($state))
                    ->wrap()
                    ->limit(50),
                Tables\Columns\TextColumn::make('conditions')
                    ->label('Conditions')
                    ->formatStateUsing(fn($state) => json_encode($state))
                    ->wrap()
                    ->limit(50),
                Tables\Columns\TextColumn::make('priority')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(PointsRuleType::cases())->mapWithKeys(fn($case) => [$case->value => $case->getLabel()]))
                    ->label('Rule Type'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Points Rule')
                    ->mutateFormDataUsing(function (array $data) {
                        // Set the product_id when creating a new rule
                        $data['product_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->where('associated_entity_type', $this->ownerRecord::class));
    }
}
