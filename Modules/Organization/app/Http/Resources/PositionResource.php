<?php

namespace Modules\Organization\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'company_id' => $this->company_id,
            'department_id' => $this->department_id,
            'job_grade_id' => $this->job_grade_id,
            'reports_to_position_id' => $this->reports_to_position_id,
            'category' => $this->category,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
