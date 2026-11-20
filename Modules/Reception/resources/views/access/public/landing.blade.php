<x-guest-layout>
    <div class="max-w-3xl mx-auto py-12 px-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('access.title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('access.subtitle') }}</p>
        <div class="mt-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ([
                ['new_employee', __('access.new_employee'), __('access.desc_new')],
                ['existing_employee', __('access.additional_access'), __('access.desc_existing')],
                ['external', __('access.external'), __('access.desc_external')],
            ] as [$type, $title, $desc])
                <a href="{{ route('access.form', $type) }}" class="rounded-sm border border-gray-200 bg-white p-6 shadow-sm hover:border-xerin-400 transition">
                    <h2 class="font-semibold text-gray-900">{{ $title }}</h2>
                    <p class="mt-1 text-xs text-gray-500">{{ $desc }}</p>
                </a>
            @endforeach
            <a href="{{ route('access.track') }}" class="rounded-sm border border-xerin-200 bg-xerin-50 p-6 hover:border-xerin-400 transition">
                <h2 class="font-semibold text-xerin-800">{{ __('access.track_request') }}</h2>
                <p class="mt-1 text-xs text-xerin-600">{{ __('access.desc_track') }}</p>
            </a>
        </div>
    </div>
</x-guest-layout>
