<x-app-layout>
    <x-slot name="header">Flight Operations</x-slot>
    <div class="space-y-4">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Schedule Flight</h3>
            <form method="POST" action="{{ route('shipment.flights.store') }}" class="flex flex-wrap gap-2">
                @csrf
                <select name="airline_id" required class="rounded border-gray-300 text-sm"><option value="">Airline…</option>
                    @foreach(\Modules\Shipment\Models\Airline::all() as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
                <input name="flight_no" required placeholder="Flight #" class="rounded border-gray-300 text-sm">
                <input name="departure_airport" placeholder="From" class="rounded border-gray-300 text-sm w-20">
                <input name="arrival_airport" placeholder="To" class="rounded border-gray-300 text-sm w-20">
                <input type="datetime-local" name="scheduled_departure" class="rounded border-gray-300 text-sm">
                <input type="datetime-local" name="scheduled_arrival" class="rounded border-gray-300 text-sm">
                <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Schedule</button>
            </form>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5 overflow-x-auto">
            <table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Flight</th><th>Route</th><th>Sched Dep</th><th>Sched Arr</th><th>Status</th><th>Event</th></tr></thead>
            <tbody class="divide-y">
                @forelse($flights as $f)
                <tr>
                    <td class="p-2 font-mono">{{ $f->airline?->code }} {{ $f->flight_no }}</td>
                    <td>{{ $f->departure_airport }} → {{ $f->arrival_airport }}</td>
                    <td>{{ $f->scheduled_departure?->format('d M H:i') ?? '—' }}</td>
                    <td>{{ $f->scheduled_arrival?->format('d M H:i') ?? '—' }}</td>
                    <td><span class="text-xs px-2 rounded {{ $f->status==='delayed'||$f->status==='cancelled'?'bg-red-100 text-red-700':'bg-indigo-100 text-indigo-700' }}">{{ $f->status }}</span></td>
                    <td><form method="POST" action="{{ route('shipment.flights.event', $f) }}" class="flex gap-1">@csrf
                        <select name="event" class="rounded border-gray-300 text-xs"><option value="departed">departed</option><option value="in_transit">in transit</option><option value="landed">landed</option><option value="delayed">delayed</option><option value="cancelled">cancelled</option><option value="diverted">diverted</option><option value="offloaded">offloaded</option></select>
                        <input type="hidden" name="source" value="manual">
                        <button class="px-2 py-0.5 bg-indigo-600 text-white rounded text-xs">Log</button></form></td>
                </tr>
                @empty<tr><td colspan="6" class="p-4 text-center text-gray-400">No flights scheduled.</td></tr>@endforelse
            </tbody></table>
        </div>
    </div>
</x-app-layout>
