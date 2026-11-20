<x-app-layout>
    <x-slot name="header">Drivers</x-slot>
    <div class="grid md:grid-cols-3 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-3">Add Driver</h3>
            <form method="POST" action="{{ route('delivery.drivers.store') }}" class="space-y-3">@csrf
                <input name="name" required placeholder="Name" class="w-full rounded border-gray-300 text-sm">
                <input name="phone" placeholder="Phone" class="w-full rounded border-gray-300 text-sm">
                <select name="branch_id" class="w-full rounded border-gray-300 text-sm">@foreach(\Modules\Organization\Models\Branch::all() as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select>
                <input name="license_no" placeholder="Licence #" class="w-full rounded border-gray-300 text-sm">
                <input type="date" name="license_expiry" class="w-full rounded border-gray-300 text-sm">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Add</button></form></div>
        <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Driver</th><th>Phone</th><th>Licence</th><th>Verified</th><th>Availability</th><th>Last Location</th><th></th></tr></thead>
            <tbody class="divide-y">@forelse($drivers as $d)
                <tr><td class="p-2 font-medium">{{ $d->name }}</td><td>{{ $d->phone }}</td><td>{{ $d->license_no }} {{ $d->license_expiry?->format('M y') }}</td>
                    <td><span class="text-xs px-2 rounded {{ $d->verification_status==='verified'?'bg-green-100 text-green-700':'bg-amber-100 text-amber-700' }}">{{ $d->verification_status }}</span></td>
                    <td><span class="text-xs px-2 rounded {{ in_array($d->availability,['available','on_delivery'])?'bg-green-100 text-green-700':'bg-gray-100' }}">{{ $d->availability }}</span></td>
                    <td class="text-xs">{{ $d->latestLocation ? $d->latestLocation->recorded_at->diffForHumans() : '—' }}</td>
                    <td>@if($d->verification_status !== 'verified')<form method="POST" action="{{ route('delivery.drivers.verify', $d) }}">@csrf<button class="text-xs px-2 py-1 bg-green-600 text-white rounded">Verify</button></form>@endif</td></tr>
                @empty<tr><td colspan="7" class="p-4 text-center text-gray-400">No drivers.</td></tr>@endforelse
            </tbody></table>{{ $drivers->links() }}</div>
    </div>
</x-app-layout>
