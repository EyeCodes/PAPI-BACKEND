<?php

namespace App\Filament\Resources\TransactionResource\Pages;

use App\Filament\Resources\TransactionResource;
use App\Services\PointsService;
use App\Models\PointsRule;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class ViewTransaction extends ViewRecord
{
    protected static string $resource = TransactionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transaction Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('Transaction ID')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Customer'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Customer Email'),
                        Infolists\Components\TextEntry::make('merchant.name')
                            ->label('Merchant'),
                        Infolists\Components\TextEntry::make('merchant.external_id')
                            ->label('Merchant ID'),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Total Amount')
                            ->money('PHP')
                            ->color('success'),
                        Infolists\Components\TextEntry::make('awarded_points')
                            ->label('Points Awarded')
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Transaction Date')
                            ->dateTime('F j, Y \a\t g:i A')
                            ->color('gray'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Customer Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('user.name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Email'),
                        Infolists\Components\TextEntry::make('user.points')
                            ->label('Current Points Balance')
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('user_roles')
                            ->label('Roles')
                            ->listWithLineBreaks()
                            ->getStateUsing(fn($record) => $record->user?->roles->pluck('name')->toArray() ?? []),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Transaction Items')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('items')
                            ->schema([
                                Infolists\Components\TextEntry::make('product.name')
                                    ->label('Product'),
                                Infolists\Components\TextEntry::make('product.price')
                                    ->label('Unit Price')
                                    ->money('PHP'),
                                Infolists\Components\TextEntry::make('quantity')
                                    ->label('Quantity'),
                                Infolists\Components\TextEntry::make('item_total_price')
                                    ->label('Total Price')
                                    ->getStateUsing(fn($record) => $record->product->price * $record->quantity)
                                    ->money('PHP')
                                    ->color('success'),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Merchant Points Rules')
                    ->schema([
                        Infolists\Components\TextEntry::make('merchant_rules')
                            ->label('Merchant Rules')
                            ->getStateUsing(function ($record) {
                                $merchantRules = $record->merchant->pointsRules()->orderBy('priority')->get();

                                if ($merchantRules->isEmpty()) {
                                    return 'No points rules assigned to this merchant.';
                                }

                                $rulesList = [];
                                foreach ($merchantRules as $rule) {
                                    $isApplicable = $this->isRuleApplicable($rule, $record);
                                    $status = $isApplicable ? '✅ Applied' : '❌ Not Applied';
                                    $rulesList[] = "**{$rule->type->getLabel()}** - {$status}";
                                    $rulesList[] = "   • Priority: {$rule->priority}";
                                    $rulesList[] = "   • Parameters: " . json_encode($rule->parameters);
                                    if ($rule->conditions) {
                                        $rulesList[] = "   • Conditions: " . json_encode($rule->conditions);
                                    }
                                    $rulesList[] = "";
                                }

                                return implode("\n", $rulesList);
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Product Points Rules')
                    ->schema([
                        Infolists\Components\TextEntry::make('product_rules')
                            ->label('Product Rules')
                            ->getStateUsing(function ($record) {
                                $productRules = collect();

                                foreach ($record->items as $item) {
                                    $itemRules = $item->product->pointsRules()->orderBy('priority')->get();
                                    if ($itemRules->isNotEmpty()) {
                                        $productRules->push("**Product: {$item->product->name}**");
                                        foreach ($itemRules as $rule) {
                                            $isApplicable = $this->isRuleApplicable($rule, $record);
                                            $status = $isApplicable ? '✅ Applied' : '❌ Not Applied';
                                            $productRules->push("  • {$rule->type->getLabel()} - {$status}");
                                            $productRules->push("    - Priority: {$rule->priority}");
                                            $productRules->push("    - Parameters: " . json_encode($rule->parameters));
                                            if ($rule->conditions) {
                                                $productRules->push("    - Conditions: " . json_encode($rule->conditions));
                                            }
                                        }
                                        $productRules->push("");
                                    }
                                }

                                if ($productRules->isEmpty()) {
                                    return 'No points rules assigned to any products in this transaction.';
                                }

                                return $productRules->implode("\n");
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Global Points Rules')
                    ->schema([
                        Infolists\Components\TextEntry::make('global_rules')
                            ->label('Global Rules')
                            ->getStateUsing(function ($record) {
                                $globalRules = PointsRule::whereNull('associated_entity_id')->orderBy('priority')->get();

                                if ($globalRules->isEmpty()) {
                                    return 'No global points rules configured.';
                                }

                                $rulesList = [];
                                foreach ($globalRules as $rule) {
                                    $isApplicable = $this->isRuleApplicable($rule, $record);
                                    $status = $isApplicable ? '✅ Applied' : '❌ Not Applied';
                                    $rulesList[] = "**{$rule->type->getLabel()}** - {$status}";
                                    $rulesList[] = "   • Priority: {$rule->priority}";
                                    $rulesList[] = "   • Parameters: " . json_encode($rule->parameters);
                                    if ($rule->conditions) {
                                        $rulesList[] = "   • Conditions: " . json_encode($rule->conditions);
                                    }
                                    $rulesList[] = "";
                                }

                                return implode("\n", $rulesList);
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Points Calculation Breakdown')
                    ->schema([
                        Infolists\Components\TextEntry::make('points_breakdown')
                            ->label('Applied Rules & Points')
                            ->getStateUsing(function ($record) {
                                if (!$record->user) {
                                    return 'No user associated with this transaction.';
                                }

                                $pointsService = new PointsService();
                                $applicableRules = $pointsService->getApplicableRules($record);

                                if ($applicableRules->isEmpty()) {
                                    return 'No applicable points rules found for this transaction.';
                                }

                                $breakdown = [];
                                $totalPoints = 0;

                                foreach ($applicableRules as $rule) {
                                    $points = $pointsService->calculatePointsForRule($rule, $record);
                                    if ($points > 0) {
                                        $breakdown[] = "**{$rule->type->getLabel()}**";
                                        $breakdown[] = "   • Points: {$points}";
                                        $breakdown[] = "   • Priority: {$rule->priority}";

                                        // Show rule source
                                        if ($rule->merchant_id) {
                                            $breakdown[] = "   • Source: Merchant Rule";
                                        } elseif ($rule->product_id) {
                                            $breakdown[] = "   • Source: Product Rule";
                                        } else {
                                            $breakdown[] = "   • Source: Global Rule";
                                        }

                                        $breakdown[] = "   • Parameters: " . json_encode($rule->parameters);
                                        if ($rule->conditions) {
                                            $breakdown[] = "   • Conditions: " . json_encode($rule->conditions);
                                        }
                                        $breakdown[] = "";

                                        $totalPoints += $points;
                                    }
                                }

                                $breakdown[] = "**Total Points Awarded: {$totalPoints}**";

                                return implode("\n", $breakdown);
                            })
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    private function isRuleApplicable(PointsRule $rule, $record): bool
    {
        $pointsService = new PointsService();

        // Check if rule meets conditions
        $conditions = is_array($rule->conditions)
            ? $rule->conditions
            : json_decode($rule->conditions ?? '[]', true);

        // Check limited time conditions
        if ($rule->type->value === 'limited_time' && $conditions) {
            $now = now();
            $start = isset($conditions['start_date']) ? \Carbon\Carbon::parse($conditions['start_date']) : null;
            $end = isset($conditions['end_date']) ? \Carbon\Carbon::parse($conditions['end_date']) : null;

            if ($start && $end) {
                return $now->gte($start) && $now->lte($end);
            }
        }

        // Check threshold conditions
        if ($rule->type->value === 'threshold' && $conditions) {
            $minAmount = $conditions['min_amount'] ?? 0;
            return $record->amount >= $minAmount;
        }

        // Check first purchase conditions
        if ($rule->type->value === 'first_purchase' && $record->user) {
            $count = $record->user->transactions()
                ->where('merchant_id', $record->merchant_id)
                ->count();
            return $count === 1;
        }

        // For other rule types, assume they're applicable if they exist
        return true;
    }
}
