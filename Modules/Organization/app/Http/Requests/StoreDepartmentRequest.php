<?php

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_definition_id' => ['nullable', 'exists:department_definitions,id'],
            'code' => ['required', 'string', 'max:30', 'alpha_dash'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'company_id' => ['required', 'exists:companies,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'effective_date' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // A branch-scoped department must reference a branch of the same company
            if ($this->branch_id && $this->company_id) {
                $ok = \Modules\Organization\Models\Branch::where('id', $this->branch_id)
                    ->where('company_id', $this->company_id)->exists();
                if (! $ok) {
                    $v->errors()->add('branch_id', 'The selected branch does not belong to the selected company.');
                }
            }
        });
    }
}
