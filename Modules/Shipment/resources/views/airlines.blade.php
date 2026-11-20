<x-app-layout>
    <x-slot name="header">Airlines &amp; Carriers</x-slot>
    <div class="grid md:grid-cols-3 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Add Airline</h3>
            <form method="POST" action="{{ route('shipment.airlines.store') }}" class="space-y-3">
                @csrf
                <input name="name" required placeholder="Airline name" class="w-full rounded border-gray-300 text-sm">
                <div class="grid grid-cols-3 gap-2">
                    <input name="code" required placeholder="Code" class="rounded border-gray-300 text-sm">
                    <input name="iata_code" placeholder="IATA" class="rounded border-gray-300 text-sm">
                    <input name="icao_code" placeholder="ICAO" class="rounded border-gray-300 text-sm">
                </div>
                <input name="contact_email" placeholder="Ops contact email" class="w-full rounded border-gray-300 text-sm">
                <input name="contract_ref" placeholder="Contract ref" class="w-full rounded border-gray-300 text-sm">
                <textarea name="cargo_restrictions" placeholder="Cargo restrictions" class="w-full rounded border-gray-300 text-sm"></textarea>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Add</button>
            </form>
        </div>
        <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">Directory</h3>
            <table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Airline</th><th>Code</th><th>IATA/ICAO</th><th>Contact</th><th>Status</th></tr></thead>
            <tbody class="divide-y">
                @forelse($airlines as $a)
                <tr><td class="p-2 font-medium">{{ $a->name }}</td><td>{{ $a->code }}</td><td>{{ $a->iata_code }}/{{ $a->icao_code }}</td><td>{{ $a->contact_email ?? '—' }}</td><td><span class="text-xs px-2 rounded bg-green-100 text-green-700">{{ $a->status }}</span></td></tr>
                @empty<tr><td colspan="5" class="p-4 text-center text-gray-400">No airlines.</td></tr>@endforelse
            </tbody></table>
        </div>
    </div>
</x-app-layout>
