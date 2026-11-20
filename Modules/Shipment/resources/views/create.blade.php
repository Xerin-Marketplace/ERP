<x-app-layout>
    <x-slot name="header">New Shipment</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('shipment.store') }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-xs text-gray-500">Origin Airport</label><input name="origin_airport" value="DXB" class="w-full rounded border-gray-300"></div>
                <div><label class="text-xs text-gray-500">Destination Airport</label><input name="destination_airport" value="DAR" class="w-full rounded border-gray-300"></div>
                <div><label class="text-xs text-gray-500">Type</label>
                    <select name="type" class="w-full rounded border-gray-300"><option value="consolidated">Consolidated</option><option value="single">Single</option><option value="partial">Partial</option></select></div>
                <div><label class="text-xs text-gray-500">Airline</label>
                    <select name="airline_id" class="w-full rounded border-gray-300"><option value="">— select —</option>
                        @foreach($airlines as $a)<option value="{{ $a->id }}">{{ $a->name }} ({{ $a->code }})</option>@endforeach
                    </select></div>
                <div><label class="text-xs text-gray-500">Planned Departure</label><input type="date" name="planned_departure" class="w-full rounded border-gray-300"></div>
                <div><label class="text-xs text-gray-500">Planned Arrival</label><input type="date" name="planned_arrival" class="w-full rounded border-gray-300"></div>
            </div>
            <button class="px-5 py-2 bg-indigo-600 text-white rounded">Create Shipment</button>
        </form>
    </div>
</x-app-layout>
