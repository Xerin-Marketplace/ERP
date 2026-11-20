<x-app-layout>
    <x-slot name="header">Trip {{ $trip->reference }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <dl class="grid grid-cols-2 gap-3 text-xs">
                <div><dt class="text-gray-400">Passenger</dt><dd>{{ $trip->passenger_name }} · {{ $trip->passenger_phone }}</dd></div>
                <div><dt class="text-gray-400">Flight</dt><dd>{{ $trip->airline }} {{ $trip->flight_number }} · {{ $trip->flight_date?->format('d M Y') }}</dd></div>
                <div><dt class="text-gray-400">Route</dt><dd>{{ $trip->originBranch->name ?? '' }} → {{ $trip->destinationBranch->name ?? '' }}</dd></div>
                <div><dt class="text-gray-400">Allowance / loaded</dt><dd class="{{ $weight['over'] ? 'text-red-600 font-bold' : '' }}">{{ $trip->baggage_allowance ?? '—' }} / {{ $weight['total_kg'] }} KG @if($weight['over']) ⚠ OVER @endif</dd></div>
                <div><dt class="text-gray-400">Status</dt><dd class="font-semibold">{{ ucfirst(str_replace('_',' ',$trip->status)) }}</dd></div>
                <div><dt class="text-gray-400">Fee</dt><dd>{{ $trip->passenger_fee }} {{ $trip->currency }}</dd></div>
            </dl>
            <table class="mt-4 w-full text-sm">
                <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500"><th class="px-3 py-2">Box</th><th class="px-3 py-2">Lines</th><th class="px-3 py-2">KG</th><th class="px-3 py-2">Status</th></tr></thead>
                <tbody>@foreach ($trip->boxes as $b)
                    <tr class="border-b border-gray-50"><td class="px-3 py-2 font-mono text-xs">{{ $b->reference }}</td>
                        <td class="px-3 py-2">{{ $b->lines->count() }}</td><td class="px-3 py-2">{{ $b->actual_weight }}</td>
                        <td class="px-3 py-2 text-xs">{{ ucfirst(str_replace('_',' ',$b->status)) }}</td></tr>
                @endforeach</tbody></table>
        </div>
        <div class="space-y-4">
            @can('cargo.trips.manage')
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-2">
                <h4 class="text-sm font-semibold">Trip Actions</h4>
                @if ($trip->status === 'assigned')
                    <form method="POST" action="{{ route('cargo.consolidation.trips.handover-dxb', $trip) }}">@csrf
                        <button class="w-full rounded-sm bg-xerin-700 px-3 py-2 text-xs font-semibold text-white">Sign Dubai handover</button></form>
                @endif
                @if ($trip->status === 'handed_over')
                    <form method="POST" action="{{ route('cargo.consolidation.trips.depart', $trip) }}" class="space-y-1">@csrf
                        <input name="evidence_file_id" type="number" required placeholder="Evidence file ID (boarding pass)" class="w-full rounded-sm border-gray-300 text-xs">
                        <button class="w-full rounded-sm bg-gray-800 px-3 py-2 text-xs font-semibold text-white">Confirm departure</button></form>
                @endif
                @if ($trip->status === 'departed')
                    <form method="POST" action="{{ route('cargo.consolidation.trips.arrive', $trip) }}">@csrf
                        <button class="w-full rounded-sm bg-xerin-700 px-3 py-2 text-xs font-semibold text-white">Mark arrived</button></form>
                @endif
                @if ($trip->status === 'arrived')
                    <form method="POST" action="{{ route('cargo.consolidation.trips.handover-tz', $trip) }}">@csrf
                        <button class="w-full rounded-sm bg-gray-800 px-3 py-2 text-xs font-semibold text-white">Sign Tanzania handover</button></form>
                @endif
            </div>
            @endcan
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm text-xs space-y-1">
                <p><span class="text-gray-400">DXB handover:</span> {{ $trip->handover_dxb_at?->format('d M H:i') ?? '—' }}</p>
                <p><span class="text-gray-400">Departed:</span> {{ $trip->departed_at?->format('d M H:i') ?? '—' }}</p>
                <p><span class="text-gray-400">Arrived:</span> {{ $trip->arrived_at?->format('d M H:i') ?? '—' }}</p>
                <p><span class="text-gray-400">TZ handover:</span> {{ $trip->handover_tz_at?->format('d M H:i') ?? '—' }}</p>
            </div>
        </div>
    </div>
</x-app-layout>
