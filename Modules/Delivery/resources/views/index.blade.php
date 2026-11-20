<x-app-layout>
    <x-slot name="header">Delivery Orders</x-slot>
    <div class="space-y-4">
        <div class="flex justify-between items-center">
            <form class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Reference…" class="rounded border-gray-300 text-sm">
                <select name="status" class="rounded border-gray-300 text-sm"><option value="">All</option>
                    @foreach(\Modules\Delivery\Models\DeliveryOrder::STATUSES as $st)<option value="{{ $st }}" @selected(request('status')===$st)>{{ str_replace('_',' ',$st) }}</option>@endforeach</select>
                <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Filter</button></form>
            <a href="{{ route('delivery.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">+ New Order</a>
        </div>
        <div class="bg-white rounded-lg shadow-sm overflow-x-auto"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-3 text-left">Reference</th><th>Customer</th><th>Zone</th><th>Driver</th><th>Fee</th><th>COD</th><th>Status</th><th>Scheduled</th></tr></thead>
            <tbody class="divide-y">
                @forelse($orders as $o)
                <tr class="hover:bg-gray-50"><td class="p-3"><a href="{{ route('delivery.show', $o) }}" class="text-indigo-600 font-medium">{{ $o->reference }}</a></td>
                    <td>{{ $o->customer?->name }}</td><td>{{ $o->zone?->name ?? '—' }}</td><td>{{ $o->driver?->name ?? '—' }}</td>
                    <td>{{ $o->fee ? number_format($o->fee) : '—' }}</td><td>{{ $o->cod_amount ? number_format($o->cod_amount) : '—' }}</td>
                    <td><span class="px-2 py-0.5 rounded text-xs {{ str_contains($o->status,'fail')||$o->status==='cancelled'?'bg-red-100 text-red-700':(in_array($o->status,['delivered','closed','collected_by_customer'])?'bg-green-100 text-green-700':'bg-indigo-100 text-indigo-700') }}">{{ str_replace('_',' ',$o->status) }}</span></td>
                    <td>{{ $o->scheduled_date?->format('d M') ?? '—' }}</td></tr>
                @empty<tr><td colspan="8" class="p-6 text-center text-gray-400">No delivery orders.</td></tr>@endforelse
            </tbody></table></div>
        {{ $orders->links() }}
    </div>
</x-app-layout>
