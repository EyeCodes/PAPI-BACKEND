<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
class CompanyResource extends JsonResource
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
            'company_id' => $this->id,
            'user_id'=>$this->user_id,
            'company_name' => $this->company_name,
            'display_name' => $this->display_name,

            'company_logo' => $this->company_logo,
            'business_type' => $this->business_type,

            'telephone_contact_1' => $this->telephone_contact_1,
            'telephone_contact_2' => $this->telephone_contact_2,
            'email_contact_1' => $this->address_contact_1,
            'email_contact_2' => $this->address_contact_2,

            'country'=>$this->country,
            'province'=>$this->province,
            'barangay' => $this->barangay,
            'city_municipality'=>$this->city_municipality,
            'region'=>$this->region,
            'zipcode'=>$this->zipcode,
            'street'=>$this->street,

            'business_registration_number'=>$this->business_registration_number,
            'tin_number'=>$this->tin_number,
            'currency_code'=>$this->currency_code,
            'created_at'=>$this->created_at,

        ];

    }
}
