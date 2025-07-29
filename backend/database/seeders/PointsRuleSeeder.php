<?php

namespace Database\Seeders;

use App\Enums\PointsRuleType;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\PointsRule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PointsRuleSeeder extends Seeder
{
    public function run()
    {
        $merchants = Merchant::all();
        $products = Product::all();

        $rules = [
            // Merchant-level rules
            [
                'type' => PointsRuleType::Fixed,
                'parameters' => ['points' => 50],
                'conditions' => [],
                'priority' => 10,
                'associated_entity_type' => Merchant::class,
            ],
            [
                'type' => PointsRuleType::Dynamic,
                'parameters' => ['divisor' => 100, 'multiplier' => 2],
                'conditions' => [],
                'priority' => 5,
                'associated_entity_type' => Merchant::class,
            ],
            [
                'type' => PointsRuleType::Combo,
                'parameters' => [
                    'divisor' => 100,
                    'amount_multiplier' => 2,
                    'quantity_multiplier' => 5,
                ],
                'conditions' => [],
                'priority' => 15,
                'associated_entity_type' => Merchant::class,
            ],
            [
                'type' => PointsRuleType::Threshold,
                'parameters' => ['points' => 100],
                'conditions' => ['min_amount' => 500],
                'priority' => 8,
                'associated_entity_type' => Merchant::class,
            ],
            [
                'type' => PointsRuleType::FirstPurchase,
                'parameters' => ['points' => 50],
                'conditions' => [],
                'priority' => 12,
                'associated_entity_type' => Merchant::class,
            ],
            [
                'type' => PointsRuleType::LimitedTime,
                'parameters' => ['points' => 75],
                'conditions' => [
                    'start_date' => Carbon::now()->subDay()->toDateTimeString(),
                    'end_date' => Carbon::now()->addDay()->toDateTimeString(),
                ],
                'priority' => 20,
                'associated_entity_type' => Merchant::class,
            ],
            [
                'type' => PointsRuleType::CustomFormula,
                'parameters' => ['formula' => 'floor(total / 100) * 2 + quantity * 5'],
                'conditions' => [],
                'priority' => 10,
                'associated_entity_type' => Merchant::class,
            ],
            // Product-level rules
            [
                'type' => PointsRuleType::Fixed,
                'parameters' => ['points' => 20],
                'conditions' => [],
                'priority' => 10,
                'associated_entity_type' => Product::class,
            ],
            [
                'type' => PointsRuleType::Combo,
                'parameters' => [
                    'divisor' => 100,
                    'amount_multiplier' => 1,
                    'quantity_multiplier' => 3,
                ],
                'conditions' => [],
                'priority' => 15,
                'associated_entity_type' => Product::class,
            ],
            [
                'type' => PointsRuleType::CustomFormula,
                'parameters' => ['formula' => 'floor(total / 50) * 1 + quantity * 2'],
                'conditions' => [],
                'priority' => 10,
                'associated_entity_type' => Product::class,
            ],
        ];

        $merchantIndex = 0;
        $productIndex = 0;

        foreach ($rules as $rule) {
            if ($rule['associated_entity_type'] === Merchant::class) {
                $rule['associated_entity_id'] = $merchants[$merchantIndex % $merchants->count()]->id;
                $merchantIndex++;
            } else {
                $rule['associated_entity_id'] = $products[$productIndex % $products->count()]->id;
                $productIndex++;
            }
            PointsRule::create($rule);
        }
    }
}
