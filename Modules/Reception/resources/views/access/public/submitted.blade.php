<x-guest-layout>
    <div class="max-w-lg mx-auto py-16 px-4 text-center">
        <h1 class="text-xl font-bold text-gray-900">{{ __('access.submitted_title') }}</h1>
        <p class="mt-2 text-sm text-gray-500">{{ __('access.need_both') }}</p>
        <div class="mt-6 rounded-sm border border-gray-200 bg-white p-6 shadow-sm text-left space-y-3">
            <div><p class="text-xs text-gray-500">{{ __('access.reference') }}</p><p class="font-mono text-lg font-bold">{{ $request->reference }}</p></div>
            <div><p class="text-xs text-gray-500">{{ __('access.tracking_token') }}</p><p class="font-mono text-xs break-all text-gray-700">{{ $request->tracking_token }}</p></div>
            <div><p class="text-xs text-gray-500">{{ __('access.status') }}</p><p class="text-sm font-semibold text-xerin-700">{{ ucfirst($request->status) }}</p></div>
        </div>
        <a href="{{ route('access.track') }}" class="mt-6 inline-block text-sm font-semibold text-xerin-700">{{ __('access.track_link') }} →</a>
    </div>
</x-guest-layout>
