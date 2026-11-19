<x-app-layout>
    <x-slot name="header">{{ __('System Settings') }}</x-slot>

    @php $canManage = auth()->user()->can('settings.manage'); @endphp

    <form method="POST" action="{{ route('admin.settings.update') }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        <fieldset @disabled(! $canManage) class="space-y-4">

            <!-- General -->
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-900">General</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Core application preferences.</p>
                </div>
                <div class="p-5 space-y-5">
                    <div>
                        <label for="app_name" class="block text-sm font-medium text-gray-700">Application name</label>
                        <input id="app_name" name="app_name" type="text" required maxlength="100"
                               value="{{ old('app_name', $values['app_name']) }}"
                               class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <x-input-error :messages="$errors->get('app_name')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                            <input id="timezone" name="timezone" type="text" required
                                   value="{{ old('timezone', $values['timezone']) }}"
                                   class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="Africa/Dar_es_Salaam">
                            <x-input-error :messages="$errors->get('timezone')" class="mt-1" />
                        </div>
                        <div>
                            <label for="locale" class="block text-sm font-medium text-gray-700">Locale</label>
                            <select id="locale" name="locale"
                                    class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['en' => 'English', 'sw' => 'Kiswahili', 'fr' => 'Français'] as $code => $name)
                                    <option value="{{ $code }}" @selected(old('locale', $values['locale']) === $code)>{{ $name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('locale')" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="date_format" class="block text-sm font-medium text-gray-700">Date format</label>
                            <select id="date_format" name="date_format"
                                    class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd M Y'] as $fmt)
                                    <option value="{{ $fmt }}" @selected(old('date_format', $values['date_format']) === $fmt)>
                                        {{ $fmt }} — {{ now()->format($fmt) }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('date_format')" class="mt-1" />
                        </div>
                        <div>
                            <label for="records_per_page" class="block text-sm font-medium text-gray-700">Records per page</label>
                            <select id="records_per_page" name="records_per_page"
                                    class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ([10, 15, 25, 50, 100] as $n)
                                    <option value="{{ $n }}" @selected((int) old('records_per_page', $values['records_per_page']) === $n)>{{ $n }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('records_per_page')" class="mt-1" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security -->
            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200">
                    <h2 class="font-semibold text-gray-900">Security</h2>
                    <p class="mt-0.5 text-xs text-gray-500">Access and session policies.</p>
                </div>
                <div class="p-5 space-y-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <div>
                            <label for="session_lifetime" class="block text-sm font-medium text-gray-700">Session lifetime (minutes)</label>
                            <input id="session_lifetime" name="session_lifetime" type="number" min="5" max="1440" required
                                   value="{{ old('session_lifetime', $values['session_lifetime']) }}"
                                   class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('session_lifetime')" class="mt-1" />
                        </div>
                        <div>
                            <label for="password_min_length" class="block text-sm font-medium text-gray-700">Minimum password length</label>
                            <input id="password_min_length" name="password_min_length" type="number" min="6" max="64" required
                                   value="{{ old('password_min_length', $values['password_min_length']) }}"
                                   class="mt-1.5 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('password_min_length')" class="mt-1" />
                        </div>
                    </div>

                    <label class="flex items-start gap-3">
                        <input type="hidden" name="require_email_verification" value="0">
                        <input type="checkbox" name="require_email_verification" value="1"
                               @checked(old('require_email_verification', $values['require_email_verification']))
                               class="mt-0.5 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        <span>
                            <span class="block text-sm font-medium text-gray-700">Require email verification</span>
                            <span class="block text-xs text-gray-500">New users must verify their email before accessing the ERP.</span>
                        </span>
                    </label>
                </div>
            </div>
        </fieldset>

        @if ($canManage)
            <button type="submit" class="mt-5 rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800">
                Save settings
            </button>
        @else
            <p class="mt-5 text-xs text-gray-400">You have read-only access to system settings.</p>
        @endif
    </form>
</x-app-layout>
