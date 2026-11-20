@php $old = old('settings', []); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('System Branding') }}</x-slot>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        <!-- Identity + theme + footer -->
        <form method="POST" action="{{ route('systemadmin.branding.update') }}" class="space-y-4">
            @csrf @method('PUT')

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Organization identity</h2></div>
                <div class="p-5 space-y-4">
                    @foreach ($defs as $key => $def)
                        @if (str_starts_with($key, 'branding.org') || str_starts_with($key, 'branding.product') || str_starts_with($key, 'branding.address') || str_starts_with($key, 'branding.email') || str_starts_with($key, 'branding.phone') || str_starts_with($key, 'branding.website'))
                            @include('systemadministration::_field', ['key' => $key, 'def' => $def, 'value' => $old[$key] ?? $values[$key]])
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Theme colors</h2></div>
                <div class="p-5 space-y-4">
                    @foreach (['branding.primary_color', 'branding.accent_color'] as $key)
                        @include('systemadministration::_field', ['key' => $key, 'def' => $defs[$key], 'value' => $old[$key] ?? $values[$key]])
                    @endforeach
                </div>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Login page</h2></div>
                <div class="p-5 space-y-4">
                    @foreach (['branding.login_heading', 'branding.login_subtext', 'branding.registration_enabled'] as $key)
                        @include('systemadministration::_field', ['key' => $key, 'def' => $defs[$key], 'value' => $old[$key] ?? $values[$key]])
                    @endforeach
                </div>
            </div>

            <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                <div class="px-5 py-4 border-b border-gray-200"><h2 class="font-semibold text-gray-900">Footer</h2></div>
                <div class="p-5 space-y-4">
                    @foreach (['branding.footer_text', 'branding.footer_year_override', 'branding.privacy_url', 'branding.terms_url'] as $key)
                        @include('systemadministration::_field', ['key' => $key, 'def' => $defs[$key], 'value' => $old[$key] ?? $values[$key]])
                    @endforeach
                    <p class="text-xs text-gray-400">Preview: © {{ $values['branding.footer_year_override'] ?: now()->year }} {{ $values['branding.footer_text'] }}</p>
                </div>
            </div>

            <button type="submit" class="rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800">Save branding</button>
        </form>

        <!-- Logo assets -->
        <div class="space-y-4">
            @foreach ($slots as $key => $description)
                @php $asset = $assets[$key]; @endphp
                <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="font-semibold text-gray-900">{{ ucwords(str_replace('_', ' ', $key)) }}</h2>
                        <p class="mt-0.5 text-xs text-gray-500">{{ $description }}</p>
                    </div>
                    <div class="p-5">
                        <div class="flex items-start gap-4">
                            <div class="flex h-16 w-40 items-center justify-center rounded-md border border-dashed border-gray-300 bg-gray-50 overflow-hidden">
                                @if ($asset['current'])
                                    <img src="{{ $asset['current']->url() }}?v={{ $asset['current']->updated_at->timestamp }}" alt="{{ $key }}" class="max-h-14 max-w-full object-contain">
                                @else
                                    <span class="text-xs text-gray-400">default asset</span>
                                @endif
                            </div>
                            <form method="POST" action="{{ route('systemadmin.branding.assets.upload') }}" enctype="multipart/form-data" class="flex-1">
                                @csrf
                                <input type="hidden" name="key" value="{{ $key }}">
                                <input type="file" name="file" required accept="image/png,image/jpeg,image/webp,image/x-icon"
                                       class="block w-full text-xs text-gray-500 file:me-3 file:rounded-md file:border-0 file:bg-gray-900 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-white hover:file:bg-gray-800">
                                <button type="submit" class="mt-2 rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500">Upload & activate</button>
                            </form>
                        </div>

                        @if ($asset['history']->count() > 1)
                            <div class="mt-4 border-t border-gray-100 pt-3">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">History</p>
                                <ul class="space-y-1.5">
                                    @foreach ($asset['history'] as $h)
                                        <li class="flex items-center gap-2 text-xs text-gray-500">
                                            <span class="flex-1 truncate">{{ $h->original_name }} — {{ $h->created_at->diffForHumans() }}</span>
                                            @if ($h->is_current)
                                                <span class="text-green-600 font-medium">current</span>
                                            @else
                                                <form method="POST" action="{{ route('systemadmin.branding.assets.restore', $h) }}">
                                                    @csrf
                                                    <button class="text-indigo-600 hover:text-indigo-500 font-medium">Restore</button>
                                                </form>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
