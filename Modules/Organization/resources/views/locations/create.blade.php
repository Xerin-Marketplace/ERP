<x-app-layout>
    <x-slot name="header">{{ __('Create Location') }}</x-slot>

    <div class="max-w-3xl rounded-sm border border-gray-200 bg-white p-6">
        <form method="POST" action="{{ route('organization.locations.store') }}">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="name" value="Location name *" />
                    <x-text-input id="name" name="name" class="block mt-1.5 w-full" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="code" value="Code *" />
                    <x-text-input id="code" name="code" class="block mt-1.5 w-full uppercase" :value="old('code')" required placeholder="e.g. WH-DSM" />
                    <x-input-error :messages="$errors->get('code')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="company_id" value="Company *" />
                    <select id="company_id" name="company_id" required class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="" disabled @selected(!old('company_id'))>Select company…</option>
                        @foreach ($companies as $c)
                            <option value="{{ $c->id }}" @selected(old('company_id') == $c->id)>{{ $c->legal_name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('company_id')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="branch_id" value="Branch (optional)" />
                    <select id="branch_id" name="branch_id" class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
                        <option value="">— None —</option>
                        @foreach ($branches as $b)
                            <option value="{{ $b->id }}" @selected(old('branch_id') == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="location_type" value="Type *" />
                    <select id="location_type" name="location_type" required class="block mt-1.5 w-full rounded-md border-gray-300 text-sm shadow-sm">
                        @foreach ($types as $t)
                            <option value="{{ $t }}" @selected(old('location_type', 'other') === $t)>{{ str_replace('_',' ',ucfirst($t)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <x-input-label for="country" value="Country" />
                    <x-text-input id="country" name="country" class="block mt-1.5 w-full" :value="old('country', 'Tanzania')" />
                </div>
                <div>
                    <x-input-label for="region" value="Region" />
                    <x-text-input id="region" name="region" class="block mt-1.5 w-full" :value="old('region')" />
                </div>
                <div>
                    <x-input-label for="district" value="District" />
                    <x-text-input id="district" name="district" class="block mt-1.5 w-full" :value="old('district')" />
                </div>
                <div>
                    <x-input-label for="city" value="City" />
                    <x-text-input id="city" name="city" class="block mt-1.5 w-full" :value="old('city')" />
                </div>
                <div>
                    <x-input-label for="physical_address" value="Physical address" />
                    <x-text-input id="physical_address" name="physical_address" class="block mt-1.5 w-full" :value="old('physical_address')" />
                </div>
                <div>
                    <x-input-label for="latitude" value="Latitude" />
                    <x-text-input id="latitude" name="latitude" class="block mt-1.5 w-full" :value="old('latitude')" placeholder="-6.7924" />
                    <x-input-error :messages="$errors->get('latitude')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="longitude" value="Longitude" />
                    <x-text-input id="longitude" name="longitude" class="block mt-1.5 w-full" :value="old('longitude')" placeholder="39.2083" />
                    <x-input-error :messages="$errors->get('longitude')" class="mt-1" />
                </div>
            </div>

            <div class="mt-8 flex items-center gap-3">
                <x-primary-button>Create location</x-primary-button>
                <a href="{{ route('organization.locations.index') }}" class="text-sm text-gray-500 hover:text-gray-700">Cancel</a>
            </div>
        </form>
    </div>
</x-app-layout>
