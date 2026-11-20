<x-app-layout>
    <x-slot name="header">MCO Trips (Passenger Carry)</x-slot>
    @can('cargo.trips.manage')
    <form method="POST" action="{{ route('cargo.consolidation.trips.store') }}" class="mb-4 grid grid-cols-2 md:grid-cols-5 gap-2 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
        @csrf
        <input name="passenger_name" required placeholder="Passenger name" class="rounded-sm border-gray-300 text-sm">
        <select name="origin_branch_id" required class="rounded-sm border-gray-300 text-sm">
            @foreach ($branches as $b) <option value="{{ $b->id }}">From: {{ $b->name }}</option> @endforeach
        </select>
        <select name="destination_branch_id" required class="rounded-sm border-gray-300 text-sm">
            @foreach ($branches as $b) <option value="{{ $b->id }}">To: {{ $b->name }}</option> @endforeach
        </select>
        <input name="flight_number" placeholder="Flight no" class="rounded-sm border-gray-300 text-sm">
        <input name="flight_date" type="date" class="rounded-sm border-gray-300 text-sm">
        <input name="passenger_id_doc" placeholder="Passenger ID" class="rounded-sm border-gray-300 text-sm">
        <input name="passenger_phone" placeholder="Phone" class="rounded-sm border-gray-300 text-sm">
        <input name="airline" placeholder="Airline" class="rounded-sm border-gray-300 text-sm">
        <input name="baggage_allowance" type="number" step="0.1" placeholder="Allowance KG" class="rounded-sm border-gray-300 text-sm">
        <input name="passenger_fee" type="number" step="0.01" placeholder="Fee" class="rounded-sm border-gray-300 text-sm">
        <button class="rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Create Trip</button>
    </form>
    @endcan
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500">
                <th class="px-4 py-2">Trip</th><th class="px-4 py-2">Passenger</th><th class="px-4 py-2">Flight</th>
                <th class="px-4 py-2">Boxes</th><th class="px-4 py-2">KG</th><th class="px-4 py-2">Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($trips as $t)
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono text-xs font-semibold">{{ $t->reference }}</td>
                    <td class="px-4 py-2 text-xs">{{ $t->passenger_name }}<p class="text-gray-400">{{ $t->passenger_phone }}</p></td>
                    <td class="px-4 py-2 text-xs">{{ $t->airline }} {{ $t->flight_number }}<p class="text-gray-400">{{ $t->flight_date?->format('d M Y') }}</p></td>
                    <td class="px-4 py-2">{{ $t->boxes->count() }}</td>
                    <td class="px-4 py-2 text-xs">{{ $t->boxes->sum('actual_weight') }} @if($t->baggage_allowance) / {{ $t->baggage_allowance }} @endif</td>
                    <td class="px-4 py-2"><span class="text-xs font-semibold">{{ ucfirst(str_replace('_',' ',$t->status)) }}</span></td>
                    <td class="px-4 py-2"><a href="{{ route('cargo.consolidation.trips.show', $t) }}" class="text-xs font-semibold text-xerin-700">Open</a></td>
                </tr>
            @empty <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No trips.</td></tr> @endforelse
            </tbody></table>
        <div class="p-4">{{ $trips->links() }}</div>
    </div>
</x-app-layout>
