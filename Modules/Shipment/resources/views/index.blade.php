<x-app-layout>
    <x-slot name="header">Shipments</x-slot>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <form class="flex gap-2">
                <input name="q" value="{{ request('q') }}" placeholder="Reference…" class="rounded border-gray-300 text-sm">
                <select name="status" class="rounded border-gray-300 text-sm">
                    <option value="">All statuses</option>
                    @foreach(\Modules\Shipment\Models\Shipment::STATUSES as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ str_replace('_',' ',$st) }}</option>
                    @endforeach
                </select>
                <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Filter</button>
            </form>
            <a href="{{ route('shipment.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">+ New Shipment</a>
        </div>
        <div class="bg-white rounded-lg shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                    <tr><th class="p-3">Reference</th><th>Route</th><th>Airline</th><th>Packages</th><th>Chargeable</th><th>Departure</th><th>Status</th></tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($shipments as $s)
                    <tr class="hover:bg-gray-50">
                        <td class="p-3"><a href="{{ route('shipment.show', $s) }}" class="text-indigo-600 font-medium">{{ $s->reference }}</a></td>
                        <td>{{ $s->origin_airport ?? '—' }} → {{ $s->destination_airport ?? '—' }}</td>
                        <td>{{ $s->airline?->name ?? '—' }}</td>
                        <td>{{ $s->total_packages }}</td>
                        <td>{{ number_format($s->total_chargeable_weight, 2) }} kg</td>
                        <td>{{ $s->planned_departure?->format('d M Y') ?? '—' }}</td>
                        <td><span class="px-2 py-0.5 rounded text-xs font-medium {{ str_contains($s->status,'delay')||$s->status==='cancelled' ? 'bg-red-100 text-red-700' : ($s->status==='arrived'||$s->status==='closed' ? 'bg-green-100 text-green-700' : 'bg-indigo-100 text-indigo-700') }}">{{ str_replace('_',' ',$s->status) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="p-6 text-center text-gray-400">No shipments yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $shipments->links() }}
    </div>
</x-app-layout>
