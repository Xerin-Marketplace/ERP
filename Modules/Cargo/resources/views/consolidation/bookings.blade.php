<x-app-layout>
    <x-slot name="header">Cargo Bookings</x-slot>
    @can('cargo.bookings.create')
    <form method="POST" action="{{ route('cargo.consolidation.bookings.store') }}" class="mb-4 grid grid-cols-2 md:grid-cols-5 gap-2 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
        @csrf
        <select name="customer_id" required class="rounded-sm border-gray-300 text-sm">
            <option value="">Customer…</option>
            @foreach ($customers as $c) <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option> @endforeach
        </select>
        <select name="branch_id" required class="rounded-sm border-gray-300 text-sm">
            @foreach ($branches as $b) <option value="{{ $b->id }}">{{ $b->name }}</option> @endforeach
        </select>
        <select name="carriage_mode" class="rounded-sm border-gray-300 text-sm">
            <option value="passenger">Passenger carry</option><option value="air_cargo">Air cargo</option>
            <option value="sea">Sea</option><option value="courier">Courier</option>
        </select>
        <select name="collection_type" class="rounded-sm border-gray-300 text-sm">
            <option value="drop_off">Drop-off</option><option value="pickup">Paid pickup</option>
        </select>
        <input name="expected_packages" type="number" min="1" placeholder="# packages" class="rounded-sm border-gray-300 text-sm">
        <input name="supplier_name" placeholder="Supplier" class="rounded-sm border-gray-300 text-sm">
        <input name="supplier_order_ref" placeholder="Supplier order ref" class="rounded-sm border-gray-300 text-sm">
        <input name="declared_weight" type="number" step="0.1" placeholder="Declared KG" class="rounded-sm border-gray-300 text-sm">
        <input name="goods_description" placeholder="Goods description" class="col-span-1 rounded-sm border-gray-300 text-sm">
        <button class="rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Create Booking</button>
    </form>
    @endcan
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500">
                <th class="px-4 py-2">Booking</th><th class="px-4 py-2">Customer</th><th class="px-4 py-2">Mode</th>
                <th class="px-4 py-2">Supplier</th><th class="px-4 py-2">Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($bookings as $b)
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono text-xs font-semibold">{{ $b->reference }}</td>
                    <td class="px-4 py-2 text-xs">{{ $b->customer->name ?? '—' }}</td>
                    <td class="px-4 py-2 text-xs">{{ str_replace('_',' ',$b->carriage_mode) }} · {{ $b->collection_type }}</td>
                    <td class="px-4 py-2 text-xs">{{ $b->supplier_name ?? '—' }}</td>
                    <td class="px-4 py-2"><span class="text-xs font-semibold">{{ ucfirst($b->status) }}</span></td>
                    <td class="px-4 py-2">
                        @if ($b->status === 'pending')
                        <form method="POST" action="{{ route('cargo.consolidation.bookings.confirm', $b) }}">@csrf
                            <button class="text-xs font-semibold text-xerin-700">Confirm</button></form>
                        @endif
                    </td>
                </tr>
            @empty <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-gray-400">No bookings.</td></tr> @endforelse
            </tbody></table>
        <div class="p-4">{{ $bookings->links() }}</div>
    </div>
</x-app-layout>
