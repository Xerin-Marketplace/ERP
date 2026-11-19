<?php

namespace Modules\Organization\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Organization\Models\Company;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // enforced via permission middleware
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:companies,code'],
            'legal_name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'company_type' => ['required', Rule::in(Company::TYPES)],
            'parent_company_id' => ['nullable', 'exists:companies,id'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tin' => ['nullable', 'string', 'max:50'],
            'vat_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url', 'max:255'],
            'country' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'physical_address' => ['nullable', 'string', 'max:255'],
            'postal_address' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'operational_start_date' => ['nullable', 'date'],
        ];
    }
}
