<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'is_active' => $this->is_active,
            'category_id' => $this->category_id,
            'is_eligible_for_points' => $this->is_eligible_for_points,
            'can_be_reward' => $this->can_be_reward,
            'description' => $this->description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'quantity' => $this->quantity,
            'expiration_date' => $this->expiration_date,
            'image_url' => $this->image_url,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->whenNotNull($this->deleted_at),
        ];
    }
}