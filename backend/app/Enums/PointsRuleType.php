<?php

namespace App\Enums;

enum PointsRuleType: string
{
    case Fixed = 'fixed';
    case Dynamic = 'dynamic';
    case Combo = 'combo';
    case Threshold = 'threshold';
    case FirstPurchase = 'first_purchase';
    case Tiered = 'tiered';
    case LimitedTime = 'limited_time';
    case MerchantOverride = 'merchant_override';
    case CategoryBased = 'category_based';
    case QuantityBonus = 'quantity_bonus';
    case NoPoints = 'no_points';
    case CustomFormula = 'custom_formula';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed Points',
            self::Dynamic => 'Dynamic Points',
            self::Combo => 'Combo Points',
            self::Threshold => 'Threshold Points',
            self::FirstPurchase => 'First Purchase Bonus',
            self::Tiered => 'Tiered Points',
            self::LimitedTime => 'Limited Time Offer',
            self::MerchantOverride => 'Merchant Override',
            self::CategoryBased => 'Category Based',
            self::QuantityBonus => 'Quantity Bonus',
            self::NoPoints => 'No Points',
            self::CustomFormula => 'Custom Formula',
        };
    }
}
