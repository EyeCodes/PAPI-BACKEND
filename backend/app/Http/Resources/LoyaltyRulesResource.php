<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyRulesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            "loyalty_program_id" => $this->id,
            "rule_name" => $this->rule_name,
            "rule_type" => $this->rule_type,
            "points_earned" => $this->points_earned,
            "amount_per_point" => $this->amount_per_point,
            "min_purchase_amount" => $this->min_purchase_amount,
            "product_category_id" => $this->product_category_id,
            "product_item_id" => $this->product_item_id,
            "is_active" => $this->is_active,
            "active_from_date" => $this->active_from_date,
            "active_to_date" => $this->active_to_date,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
