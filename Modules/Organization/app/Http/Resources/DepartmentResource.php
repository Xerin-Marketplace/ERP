<?php

namespace Modules\Organization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'company_id' => $this->company_id,
            'branch_id' => $this->branch_id,
            'parent_id' => $this->parent_id,
            'head_id' => $this->head_id,
            'status' => $this->status?->value ?? $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
