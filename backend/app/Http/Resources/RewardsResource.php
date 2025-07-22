<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
            //return parent::toArray($request);

        return[
            'id' => $this->id,
            'loyalty_program_rule_id' => $this->loyalty_program_rule_id,
            'reward_name' => $this->reward_name,
            'description' => $this->description,
            'reward_type' => $this->reward_type,
            'point_cost' => $this->point_cost,
            'discount_value' => $this->discount_value,
            'discount_percentage' => $this->discount_percentage,

            'item_id' => $this->item_id,
            'voucher_code' => $this->voucher_code,
            'is_active' => (bool) $this->is_active,
            'max_redemption_rate' => $this->max_redemption_rate,
            'expiration_days' => $this->expiration_days,
            'created_at' => $this->created_at,
        ];
    }
}


