<?php

namespace Modules\Organization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'branch_type' => $this->branch_type,
            'company' => $this->whenLoaded('company', fn () => [
                'id' => $this->company->id,
                'code' => $this->company->code,
                'legal_name' => $this->company->legal_name,
            ]),
            'country' => $this->country,
            'country_code' => $this->country_code,
            'region' => $this->region,
            'city' => $this->city,
            'physical_address' => $this->physical_address,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'currency' => $this->currency,
            'timezone' => $this->timezone,
            'status' => $this->status?->value ?? $this->status,
            'activated_at' => $this->activated_at,
            'deactivated_at' => $this->deactivated_at,
            'departments_count' => $this->whenCounted('departments'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
