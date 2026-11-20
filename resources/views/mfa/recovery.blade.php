<x-app-layout>
    <x-slot name="header">MFA Enabled — Recovery Codes</x-slot>
    <div class="max-w-lg rounded-sm border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-gray-600">Save these codes somewhere safe. Each works once if you lose your device.
            <strong class="text-red-600">They are shown only now.</strong></p>
        <div class="mt-4 grid grid-cols-2 gap-2 rounded-sm bg-gray-50 p-4 font-mono text-sm">
            @foreach ($codes as $code) <p class="select-all">{{ $code }}</p> @endforeach
        </div>
        <a href="{{ route('dashboard') }}" class="mt-4 inline-block rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Done</a>
    </div>
</x-app-layout>
