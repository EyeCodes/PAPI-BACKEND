<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyProgramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'company_id'   => $this->company_id,
            'program_name' => $this->program_name,
            'description'  => $this->description,
            'program_type' => $this->program_type,
            'is_active'    => $this->is_active,
            'start_date'   => $this->start_date,
            'end_date'     => $this->end_date,
            'instructions' => $this->instructions,
            'created_at'   => $this->created_at,
            'updated_at'   => $this->updated_at,
        ];
    }
}
