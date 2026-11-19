<x-app-layout>
    <x-slot name="header">{{ __('Service Desk Dashboard') }}</x-slot>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        @php $cards = [
            ['Open Tickets', $metrics['open'], 'text-blue-600'], ['Unassigned', $metrics['unassigned'], 'text-amber-600'],
            ['Critical', $metrics['critical'], 'text-red-600'], ['SLA Breaches', $metrics['sla_breached'], 'text-red-700'],
            ['Assigned to Me', $metrics['mine'], 'text-xerin-700'], ['My Requests', $metrics['requested'], 'text-gray-900'],
            ['My Open Tasks', $metrics['my_tasks'], 'text-indigo-600'], ['Escalations', $metrics['escalations'], 'text-purple-600'],
        ]; @endphp
        @foreach ($cards as [$label, $v, $c])
            <a href="{{ route('ticketing.index') }}" class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm block">
                <p class="text-xs text-gray-500">{{ $label }}</p><p class="mt-1 text-xl font-bold {{ $c }}">{{ $v }}</p></a>
        @endforeach
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">By Status</h3>
            <div class="space-y-1.5 text-sm">@foreach ($metrics['by_status'] as $s => $t)
                <div class="flex justify-between"><span class="capitalize">{{ str_replace('_',' ',$s) }}</span><span class="font-semibold">{{ $t }}</span></div>
            @endforeach</div>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">By Priority</h3>
            <div class="space-y-1.5 text-sm">@foreach ($metrics['by_priority'] as $p => $t)
                <div class="flex justify-between"><span class="capitalize">{{ $p }}</span><span class="font-semibold">{{ $t }}</span></div>
            @endforeach</div>
        </div>
    </div>
</x-app-layout>
