<?php

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('organization.positions.update') ?? false;
    }

    public function rules(): array
    {
        $position = $this->route('position');

        return [
            'company_id' => ['sometimes', 'exists:companies,id'],
            'code' => ['sometimes', 'string', 'max:30', 'alpha_dash',
                       Rule::unique('job_positions', 'code')->ignore($position?->id)],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'job_grade_id' => ['nullable', 'exists:job_grades,id'],
            'reports_to_position_id' => ['nullable', 'exists:job_positions,id',
                                        Rule::notIn([$position?->id])],
            'category' => ['nullable', 'string', 'max:60'],
            'minimum_qualifications' => ['nullable', 'string', 'max:2000'],
            'responsibilities' => ['nullable', 'string', 'max:4000'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ];
    }
}
