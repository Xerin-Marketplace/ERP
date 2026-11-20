<x-app-layout>
    <x-slot name="header">{{ __('Localization') }}</x-slot>

    <form method="POST" action="{{ route('systemadmin.localization.update') }}" class="max-w-xl">
        @csrf @method('PUT')
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-900">Language configuration</h2>
                <p class="mt-0.5 text-xs text-gray-500">Financial account codes, legal references and historical records are never translated.</p>
            </div>
            <div class="p-5 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Default language</label>
                    <select name="system.locale" class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($options as $code => $name)
                            <option value="{{ $code }}" @selected($locale === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Available languages</label>
                    <div class="mt-1.5 space-y-2">
                        @foreach ($options as $code => $name)
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="system.available_locales[]" value="{{ $code }}" @checked(in_array($code, (array) $available))
                                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"> {{ $name }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" class="mt-5 rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800">Save localization</button>
    </form>
</x-app-layout>
