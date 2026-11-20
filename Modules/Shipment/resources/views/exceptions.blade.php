<x-app-layout>
    <x-slot name="header">Shipment Exceptions</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
        <table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Ref</th><th>Shipment</th><th>Category</th><th>Severity</th><th>Status</th><th>Reported</th></tr></thead>
        <tbody class="divide-y">
            @forelse($exceptions as $e)
            <tr><td class="p-2 font-mono">{{ $e->reference }}</td>
                <td><a href="{{ route('shipment.show', $e->shipment_id) }}" class="text-indigo-600">{{ $e->shipment?->reference }}</a></td>
                <td class="capitalize">{{ str_replace('_',' ',$e->category) }}</td>
                <td><span class="text-xs px-2 rounded {{ $e->severity==='high'||$e->severity==='critical'?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700' }}">{{ $e->severity }}</span></td>
                <td>{{ $e->status }}</td><td>{{ $e->created_at->format('d M') }}</td></tr>
            @empty<tr><td colspan="6" class="p-4 text-center text-gray-400">No exceptions.</td></tr>@endforelse
        </tbody></table>
        {{ $exceptions->links() }}
    </div>
</x-app-layout>
