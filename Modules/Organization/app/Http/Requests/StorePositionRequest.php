<?php

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('organization.positions.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash', 'unique:job_positions,code'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'job_grade_id' => ['nullable', 'exists:job_grades,id'],
            'reports_to_position_id' => ['nullable', 'exists:job_positions,id'],
            'category' => ['nullable', 'string', 'max:60'],
            'minimum_qualifications' => ['nullable', 'string', 'max:2000'],
            'responsibilities' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
