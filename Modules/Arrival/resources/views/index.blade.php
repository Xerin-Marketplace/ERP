<x-app-layout>
    <x-slot name="header">TZ Arrivals</x-slot>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <form class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Ref or shipment…" class="rounded border-gray-300 text-sm">
                <select name="status" class="rounded border-gray-300 text-sm"><option value="">All</option>
                    @foreach(\Modules\Arrival\Models\ShipmentArrival::STATUSES as $st)<option value="{{ $st }}" @selected(request('status')===$st)>{{ str_replace('_',' ',$st) }}</option>@endforeach</select>
                <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Filter</button></form>
            <a href="{{ route('arrival.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">+ Register Arrival</a>
        </div>
        <div class="bg-white rounded-lg shadow-sm overflow-x-auto"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-3 text-left">Arrival Ref</th><th>Shipment</th><th>MAWB</th><th>Flight</th><th>Expected/Received</th><th>Status</th><th>Actual Arrival</th></tr></thead>
            <tbody class="divide-y">
                @forelse($arrivals as $a)
                <tr class="hover:bg-gray-50"><td class="p-3"><a href="{{ route('arrival.show', $a) }}" class="text-indigo-600 font-medium">{{ $a->reference }}</a></td>
                    <td>{{ $a->shipment?->reference }}</td><td class="font-mono">{{ $a->mawb ?? '—' }}</td><td>{{ $a->flight_no ?? '—' }}</td>
                    <td class="text-center">{{ $a->received_packages }}/{{ $a->expected_packages }}</td>
                    <td><span class="px-2 py-0.5 rounded text-xs {{ str_contains($a->status,'discrep')||$a->status==='on_hold'?'bg-red-100 text-red-700':($a->status==='closed'||$a->status==='fully_received'?'bg-green-100 text-green-700':'bg-indigo-100 text-indigo-700') }}">{{ str_replace('_',' ',$a->status) }}</span></td>
                    <td>{{ $a->actual_arrival?->format('d M H:i') ?? '—' }}</td></tr>
                @empty<tr><td colspan="7" class="p-6 text-center text-gray-400">No arrivals registered.</td></tr>@endforelse
            </tbody></table></div>
        {{ $arrivals->links() }}
    </div>
</x-app-layout>
