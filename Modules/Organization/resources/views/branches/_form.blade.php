@php $b = $branch ?? null; @endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <x-input-label for="name" value="Branch name *" />
        <x-text-input id="name" name="name" class="block mt-1.5 w-full" :value="old('name', $b?->name)" required />
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="code" value="Branch code *" />
        <x-text-input id="code" name="code" class="block mt-1.5 w-full uppercase" :value="old('code', $b?->code)" required placeholder="e.g. DSM-01" />
        <x-input-error :messages="$errors->get('code')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="company_id" value="Company *" />
        <select id="company_id" name="company_id" required class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="" disabled @selected(!old('company_id', $b?->company_id))>Select company…</option>
            @foreach ($companies as $co)
                <option value="{{ $co->id }}" @selected(old('company_id', $b?->company_id) == $co->id)>{{ $co->legal_name }}</option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('company_id')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="branch_type" value="Branch type *" />
        <select id="branch_type" name="branch_type" required class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
            @foreach ($types as $t)
                <option value="{{ $t }}" @selected(old('branch_type', $b?->branch_type ?? 'branch') === $t)>{{ str_replace('_',' ',ucfirst($t)) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <x-input-label for="contact_person" value="Contact person" />
        <x-text-input id="contact_person" name="contact_person" class="block mt-1.5 w-full" :value="old('contact_person', $b?->contact_person)" />
    </div>
    <div>
        <x-input-label for="phone" value="Phone" />
        <x-text-input id="phone" name="phone" class="block mt-1.5 w-full" :value="old('phone', $b?->phone)" />
    </div>
    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="block mt-1.5 w-full" :value="old('email', $b?->email)" />
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="opening_date" value="Opening date" />
        <x-text-input id="opening_date" name="opening_date" type="date" class="block mt-1.5 w-full" :value="old('opening_date', $b?->opening_date?->format('Y-m-d'))" />
    </div>
    <div>
        <x-input-label for="country" value="Country" />
        <x-text-input id="country" name="country" class="block mt-1.5 w-full" :value="old('country', $b?->country ?? 'Tanzania')" />
    </div>
    <div>
        <x-input-label for="region" value="Region" />
        <x-text-input id="region" name="region" class="block mt-1.5 w-full" :value="old('region', $b?->region)" />
    </div>
    <div>
        <x-input-label for="district" value="District" />
        <x-text-input id="district" name="district" class="block mt-1.5 w-full" :value="old('district', $b?->district)" />
    </div>
    <div>
        <x-input-label for="city" value="City" />
        <x-text-input id="city" name="city" class="block mt-1.5 w-full" :value="old('city', $b?->city)" />
    </div>
    <div>
        <x-input-label for="physical_address" value="Physical address" />
        <x-text-input id="physical_address" name="physical_address" class="block mt-1.5 w-full" :value="old('physical_address', $b?->physical_address)" />
    </div>
    <div>
        <x-input-label for="postal_address" value="Postal address" />
        <x-text-input id="postal_address" name="postal_address" class="block mt-1.5 w-full" :value="old('postal_address', $b?->postal_address)" />
    </div>
    <div>
        <x-input-label for="latitude" value="Latitude" />
        <x-text-input id="latitude" name="latitude" class="block mt-1.5 w-full" :value="old('latitude', $b?->latitude)" placeholder="-6.7924" />
        <x-input-error :messages="$errors->get('latitude')" class="mt-1" />
    </div>
    <div>
        <x-input-label for="longitude" value="Longitude" />
        <x-text-input id="longitude" name="longitude" class="block mt-1.5 w-full" :value="old('longitude', $b?->longitude)" placeholder="39.2083" />
        <x-input-error :messages="$errors->get('longitude')" class="mt-1" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="description" value="Description" />
        <textarea id="description" name="description" rows="3" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $b?->description) }}</textarea>
    </div>
</div>

<div class="mt-8 flex items-center gap-3">
    <x-primary-button>{{ $b ? 'Save changes' : 'Create branch' }}</x-primary-button>
    <a href="{{ $b ? route('organization.branches.show', $b) : route('organization.branches.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
</div>
