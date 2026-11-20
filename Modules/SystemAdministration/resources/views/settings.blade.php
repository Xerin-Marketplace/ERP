@php $old = old('settings', []); @endphp
<x-app-layout>
    <x-slot name="header">{{ __('General Settings') }}</x-slot>

    <div class="mb-5 flex gap-2">
        @foreach ($groups as $g)
            <a href="{{ route('systemadmin.settings', $g) }}"
               class="rounded-md px-4 py-2 text-sm font-medium {{ $g === $group ? 'bg-gray-900 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">{{ $g }}</a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('systemadmin.settings.update', $group) }}" class="max-w-3xl">
        @csrf @method('PUT')
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-gray-900">{{ $group }} settings</h2>
            </div>
            <div class="p-5 space-y-5">
                @foreach ($defs as $key => $def)
                    @include('systemadministration::_field', ['key' => $key, 'def' => $def, 'value' => $old[$key] ?? $values[$key]])
                @endforeach
            </div>
        </div>
        <button type="submit" class="mt-5 rounded-md bg-gray-900 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-800">Save {{ strtolower($group) }} settings</button>
    </form>
</x-app-layout>
