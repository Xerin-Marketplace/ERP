<?php

namespace Modules\Identity\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Organization\Enums\EntityStatus;

class SubmitRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // public endpoint — validation + rate limiting protect it
    }

    public function rules(): array
    {
        $activeCompany = ['nullable', 'exists:companies,id'];
        $activeBranch = ['nullable', 'exists:branches,id'];
        $activeDept = ['nullable', 'exists:departments,id'];

        return [
            // Personal
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'alt_phone' => ['nullable', 'string', 'max:30'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'residential_address' => ['nullable', 'string', 'max:500'],
            // Employment request
            'employee_number' => ['nullable', 'string', 'max:50'],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:150'],
            'requested_company_id' => $activeCompany,
            'requested_branch_id' => $activeBranch,
            'requested_department_id' => $activeDept,
            'employment_start_date' => ['nullable', 'date'],
            'registration_reason' => ['nullable', 'string', 'max:2000'],
            // Documents
            'employment_id_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'supporting_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            // Cross-check requested org relations — requests are not authoritative
            // but obvious nonsense is rejected early.
            if ($this->requested_branch_id && $this->requested_company_id) {
                $ok = \Modules\Organization\Models\Branch::where('id', $this->requested_branch_id)
                    ->where('company_id', $this->requested_company_id)->exists();
                if (! $ok) {
                    $v->errors()->add('requested_branch_id', 'The selected branch does not belong to the selected company.');
                }
            }
        });
    }
}
