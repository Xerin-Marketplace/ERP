<x-app-layout>
    <x-slot name="header">Register TZ Arrival</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-6 max-w-xl">
        <form method="POST" action="{{ route('arrival.store') }}" class="space-y-4">@csrf
            <div><label class="text-xs text-gray-500">Inbound Shipment</label>
                <select name="shipment_id" required class="w-full rounded border-gray-300">
                    @foreach($shipments as $s)<option value="{{ $s->id }}">{{ $s->reference }} ({{ $s->origin_airport }}→{{ $s->destination_airport }}, {{ $s->total_packages }} pkgs)</option>@endforeach
                </select></div>
            <div><label class="text-xs text-gray-500">Receiving Branch</label>
                <select name="branch_id" required class="w-full rounded border-gray-300">
                    @foreach($branches as $b)<option value="{{ $b->id }}">{{ $b->name }} ({{ $b->code }})</option>@endforeach</select></div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-xs text-gray-500">Dest Airport</label><input name="destination_airport" value="DAR" class="w-full rounded border-gray-300"></div>
                <div><label class="text-xs text-gray-500">Scheduled Arrival</label><input type="datetime-local" name="scheduled_arrival" class="w-full rounded border-gray-300"></div>
            </div>
            <button class="px-5 py-2 bg-indigo-600 text-white rounded">Register Arrival</button>
        </form>
    </div>
</x-app-layout>
