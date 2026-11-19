<x-app-layout>
    <x-slot name="header">{{ __('Service Catalog') }}</x-slot>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($services as $s)
            <a href="{{ route('ticketing.catalog.request', $s) }}" class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm hover:border-xerin-400 transition block">
                <div class="flex justify-between items-start">
                    <h3 class="font-semibold text-gray-900">{{ $s->name }}</h3>
                    @if ($s->requires_approval)<span class="text-[10px] uppercase bg-amber-100 text-amber-700 rounded px-1.5 py-0.5">approval</span>@endif
                </div>
                <p class="mt-1 text-sm text-gray-500 line-clamp-2">{{ $s->description }}</p>
                <p class="mt-2 text-xs text-gray-400">{{ $s->category?->name ?? 'General' }}{{ $s->expected_hours ? ' · ~'.$s->expected_hours.'h' : '' }}</p>
            </a>
        @empty
            <p class="text-gray-400">No services available.</p>
        @endforelse
    </div>
</x-app-layout>
