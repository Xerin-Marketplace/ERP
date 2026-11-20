<x-app-layout>
    <x-slot name="header">Box {{ $box->reference }}</x-slot>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <div class="flex justify-between">
                <div>
                    <p class="text-xs text-gray-500">Status</p>
                    <p class="font-semibold">{{ ucfirst(str_replace('_',' ',$box->status)) }}</p>
                </div>
                <div><p class="text-xs text-gray-500">Lines weight</p><p class="font-semibold">{{ number_format($linesWeight,1) }} KG</p></div>
                <div><p class="text-xs text-gray-500">Seal</p><p class="font-mono text-sm">{{ $box->seal_number ?? '—' }}</p></div>
            </div>
            <table class="mt-4 w-full text-sm">
                <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500">
                    <th class="px-3 py-2">Package</th><th class="px-3 py-2">Tracking</th><th class="px-3 py-2">KG</th></tr></thead>
                <tbody>
                @foreach ($box->lines as $l)
                    <tr class="border-b border-gray-50">
                        <td class="px-3 py-2 font-mono text-xs">{{ $l->package->reference ?? $l->package_id }}</td>
                        <td class="px-3 py-2 font-mono text-xs">{{ $l->package->tracking_no ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $l->weight }}</td></tr>
                @endforeach
                </tbody></table>
        </div>
        <div class="space-y-4">
            @can('cargo.consolidation.manage')
            @if ($box->status === 'open')
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-3">
                <h4 class="text-sm font-semibold">Add Package</h4>
                <form method="POST" action="{{ route('cargo.consolidation.boxes.lines', $box) }}" class="space-y-2">
                    @csrf
                    <select name="package_id" required class="w-full rounded-sm border-gray-300 text-sm">
                        <option value="">Select package…</option>
                        @foreach ($packages as $p)
                            <option value="{{ $p->id }}">{{ $p->reference }} ({{ $p->tracking_no ?? 'no-tracking' }}) — {{ $p->gross_weight }}KG</option>
                        @endforeach
                    </select>
                    <input name="weight" type="number" step="0.1" placeholder="Weight KG (default: package weight)" class="w-full rounded-sm border-gray-300 text-sm">
                    <button class="w-full rounded-sm bg-xerin-700 px-3 py-2 text-xs font-semibold text-white">Add to box</button>
                </form>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-3">
                <h4 class="text-sm font-semibold">Close &amp; Seal</h4>
                <form method="POST" action="{{ route('cargo.consolidation.boxes.close', $box) }}" class="space-y-2">
                    @csrf
                    <input name="actual_weight" type="number" step="0.1" required placeholder="Scale weight KG" class="w-full rounded-sm border-gray-300 text-sm">
                    <input name="seal_number" required placeholder="Seal number" class="w-full rounded-sm border-gray-300 text-sm">
                    <p class="text-xs text-gray-400">Lines weight {{ number_format($linesWeight,1) }} KG — tolerance {{ \Modules\Cargo\Services\ConsolidationService::WEIGHT_TOLERANCE }} KG.</p>
                    <button class="w-full rounded-sm bg-gray-800 px-3 py-2 text-xs font-semibold text-white">Close box</button>
                </form>
            </div>
            @elseif ($box->status === 'closed')
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-3">
                <h4 class="text-sm font-semibold">Assign to Trip</h4>
                <form method="POST" action="{{ route('cargo.consolidation.boxes.assign', $box) }}" class="space-y-2">
                    @csrf
                    <select name="trip_id" required class="w-full rounded-sm border-gray-300 text-sm">
                        @foreach (\Modules\Cargo\Models\CargoTrip::whereIn('status',['planned','assigned'])->get() as $t)
                            <option value="{{ $t->id }}">{{ $t->reference }} — {{ $t->passenger_name }} ({{ $t->flight_date?->format('d M') }})</option>
                        @endforeach
                    </select>
                    <button class="w-full rounded-sm bg-xerin-700 px-3 py-2 text-xs font-semibold text-white">Assign</button>
                </form>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm space-y-3">
                <h4 class="text-sm font-semibold">Load onto Shipment (AWB)</h4>
                <form method="POST" action="{{ route('cargo.consolidation.boxes.assign-shipment', $box) }}" class="space-y-2">
                    @csrf
                    <select name="shipment_id" required class="w-full rounded-sm border-gray-300 text-sm">
                        @foreach (\Modules\Shipment\Models\Shipment::whereIn('status', \Modules\Cargo\Services\ConsolidationService::SHIPMENT_LOADING_STATUSES)->orderByDesc('id')->limit(50)->get() as $s)
                            <option value="{{ $s->id }}">{{ $s->reference }} — {{ $s->origin_airport }}→{{ $s->destination_airport }} ({{ $s->status }})</option>
                        @endforeach
                    </select>
                    <button class="w-full rounded-sm bg-xerin-700 px-3 py-2 text-xs font-semibold text-white">Load onto shipment</button>
                </form>
            </div>
            @endif
            @endcan
        </div>
    </div>
</x-app-layout>
