@php $c = $company ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="legal_name" value="Legal name *" />
        <x-text-input id="legal_name" name="legal_name" class="block mt-1.5 w-full" :value="old('legal_name', $c?->legal_name)" required />
        <x-input-error :messages="$errors->get('legal_name')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="trading_name" value="Trading name" />
        <x-text-input id="trading_name" name="trading_name" class="block mt-1.5 w-full" :value="old('trading_name', $c?->trading_name)" />
    </div>
    <div>
        <x-input-label for="code" value="Company code *" />
        <x-text-input id="code" name="code" class="block mt-1.5 w-full uppercase" :value="old('code', $c?->code)" required placeholder="e.g. XG-LOG" />
        <x-input-error :messages="$errors->get('code')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="company_type" value="Company type *" />
        <select id="company_type" name="company_type" required class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach ($types as $t)
                <option value="{{ $t }}" @selected(old('company_type', $c?->company_type ?? 'subsidiary') === $t)>{{ ucfirst($t) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="parent_company_id" value="Parent company" />
        <select id="parent_company_id" name="parent_company_id" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            <option value="">— None (root company) —</option>
            @foreach ($parents as $p)
                <option value="{{ $p->id }}" @selected(old('parent_company_id', $c?->parent_company_id) == $p->id)>{{ $p->legal_name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('parent_company_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="registration_number" value="Registration number" />
        <x-text-input id="registration_number" name="registration_number" class="block mt-1.5 w-full" :value="old('registration_number', $c?->registration_number)" />
    </div>
    <div>
        <x-input-label for="tin" value="TIN" />
        <x-text-input id="tin" name="tin" class="block mt-1.5 w-full" :value="old('tin', $c?->tin)" placeholder="Tax Identification Number" />
    </div>
    <div>
        <x-input-label for="vat_number" value="VAT number" />
        <x-text-input id="vat_number" name="vat_number" class="block mt-1.5 w-full" :value="old('vat_number', $c?->vat_number)" />
    </div>
    <div>
        <x-input-label for="email" value="Contact email" />
        <x-text-input id="email" name="email" type="email" class="block mt-1.5 w-full" :value="old('email', $c?->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="phone" value="Phone" />
        <x-text-input id="phone" name="phone" class="block mt-1.5 w-full" :value="old('phone', $c?->phone)" />
    </div>
    <div>
        <x-input-label for="website" value="Website" />
        <x-text-input id="website" name="website" type="url" class="block mt-1.5 w-full" :value="old('website', $c?->website)" placeholder="https://" />
        <x-input-error :messages="$errors->get('website')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="operational_start_date" value="Operational start date" />
        <x-text-input id="operational_start_date" name="operational_start_date" type="date" class="block mt-1.5 w-full" :value="old('operational_start_date', $c?->operational_start_date?->format('Y-m-d'))" />
    </div>
    <div>
        <x-input-label for="country" value="Country" />
        <x-text-input id="country" name="country" class="block mt-1.5 w-full" :value="old('country', $c?->country ?? 'Tanzania')" />
    </div>
    <div>
        <x-input-label for="region" value="Region" />
        <x-text-input id="region" name="region" class="block mt-1.5 w-full" :value="old('region', $c?->region)" />
    </div>
    <div>
        <x-input-label for="district" value="District" />
        <x-text-input id="district" name="district" class="block mt-1.5 w-full" :value="old('district', $c?->district)" />
    </div>
    <div>
        <x-input-label for="city" value="City" />
        <x-text-input id="city" name="city" class="block mt-1.5 w-full" :value="old('city', $c?->city)" />
    </div>
    <div>
        <x-input-label for="physical_address" value="Physical address" />
        <x-text-input id="physical_address" name="physical_address" class="block mt-1.5 w-full" :value="old('physical_address', $c?->physical_address)" />
    </div>
    <div>
        <x-input-label for="postal_address" value="Postal address" />
        <x-text-input id="postal_address" name="postal_address" class="block mt-1.5 w-full" :value="old('postal_address', $c?->postal_address)" />
    </div>
    <div>
        <x-input-label for="currency" value="Default currency" />
        <x-text-input id="currency" name="currency" maxlength="3" class="block mt-1.5 w-full uppercase" :value="old('currency', $c?->currency ?? 'TZS')" />
    </div>
    <div>
        <x-input-label for="timezone" value="Timezone" />
        <x-text-input id="timezone" name="timezone" class="block mt-1.5 w-full" :value="old('timezone', $c?->timezone ?? 'Africa/Dar_es_Salaam')" />
    </div>
</div>

<div class="mt-8 flex items-center gap-3">
    <x-primary-button>{{ $c ? 'Save changes' : 'Create company' }}</x-primary-button>
    <a href="{{ $c ? route('organization.companies.show', $c) : route('organization.companies.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
