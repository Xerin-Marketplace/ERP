<?php

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $department = $this->route('department');

        return [
            'department_definition_id' => ['nullable', 'exists:department_definitions,id'],
            'code' => ['sometimes', 'string', 'max:30', 'alpha_dash'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'company_id' => ['sometimes', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'parent_id' => ['nullable', 'exists:departments,id', Rule::notIn([$department?->id])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'effective_date' => ['nullable', 'date'],
        ];
    }
}
