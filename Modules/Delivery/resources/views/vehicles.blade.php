<x-app-layout>
    <x-slot name="header">Vehicles</x-slot>
    <div class="grid md:grid-cols-3 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-3">Add Vehicle</h3>
            <form method="POST" action="{{ route('delivery.vehicles.store') }}" class="space-y-3">@csrf
                <input name="registration_no" required placeholder="Reg no" class="w-full rounded border-gray-300 text-sm">
                <select name="branch_id" class="w-full rounded border-gray-300 text-sm">@foreach(\Modules\Organization\Models\Branch::all() as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
                <select name="type" class="w-full rounded border-gray-300 text-sm"><option value="bike">Bike</option><option value="car">Car</option><option value="van">Van</option><option value="truck">Truck</option></select>
                <input name="capacity_kg" placeholder="Capacity kg" class="w-full rounded border-gray-300 text-sm">
                <input name="capacity_packages" placeholder="Max packages" class="w-full rounded border-gray-300 text-sm">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Add</button></form></div>
        <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Reg</th><th>Type</th><th>Capacity</th><th>Insurance</th><th>Status</th></tr></thead>
            <tbody class="divide-y">@forelse($vehicles as $v)
                <tr><td class="p-2 font-medium">{{ $v->registration_no }}</td><td>{{ $v->type }}</td><td>{{ $v->capacity_kg }}kg/{{ $v->capacity_packages }}pk</td>
                    <td>{{ $v->insurance_expiry?->format('d M y') ?? '—' }}</td><td><span class="text-xs px-2 rounded {{ $v->status==='active'?'bg-green-100 text-green-700':'bg-red-100 text-red-700' }}">{{ $v->status }}</span></td></tr>
                @empty<tr><td colspan="5" class="p-4 text-center text-gray-400">No vehicles.</td></tr>@endforelse
            </tbody></table>{{ $vehicles->links() }}</div>
    </div>
</x-app-layout>
