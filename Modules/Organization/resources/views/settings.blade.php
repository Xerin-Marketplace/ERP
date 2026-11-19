<x-app-layout>
    <x-slot name="header">{{ __('Organization Settings') }}</x-slot>

    <form method="POST" action="{{ route('organization.settings.update') }}" class="max-w-xl">
        @csrf
        @method('PUT')

        <div class="rounded-sm border border-gray-200 bg-white p-6 shadow-sm space-y-5">
            <div>
                <label for="default_country" class="block text-sm font-medium text-gray-700">Default country</label>
                <input id="default_country" name="default_country" type="text" required
                       value="{{ old('default_country', $defaults['default_country']) }}"
                       class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <x-input-error :messages="$errors->get('default_country')" class="mt-1" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label for="default_currency" class="block text-sm font-medium text-gray-700">Default currency</label>
                    <input id="default_currency" name="default_currency" type="text" required maxlength="3"
                           value="{{ old('default_currency', $defaults['default_currency']) }}"
                           class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm uppercase focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('default_currency')" class="mt-1" />
                </div>
                <div>
                    <label for="default_timezone" class="block text-sm font-medium text-gray-700">Default timezone</label>
                    <input id="default_timezone" name="default_timezone" type="text" required
                           value="{{ old('default_timezone', $defaults['default_timezone']) }}"
                           class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('default_timezone')" class="mt-1" />
                </div>
            </div>

            <label class="flex items-start gap-3">
                <input type="hidden" name="require_branch_for_departments" value="0">
                <input type="checkbox" name="require_branch_for_departments" value="1"
                       @checked(old('require_branch_for_departments', $defaults['require_branch_for_departments']))
                       class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span>
                    <span class="block text-sm font-medium text-gray-700">Require branch for departments</span>
                    <span class="block text-xs text-gray-500">When enabled, every new department must be assigned to a branch.</span>
                </span>
            </label>
        </div>

        <button type="submit" class="mt-5 rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800">
            Save settings
        </button>
    </form>
</x-app-layout>
