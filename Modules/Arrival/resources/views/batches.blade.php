<x-app-layout>
    <x-slot name="header">Distribution Batches</x-slot>
    <div class="space-y-4">
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-2">Create Batch (release-ready packages only)</h3>
            <form method="POST" action="{{ route('arrival.batches.store') }}" class="flex flex-wrap gap-2">@csrf
                <input name="package_ids[]" required placeholder="Package IDs" class="rounded border-gray-300 text-sm w-40">
                <select name="branch_id" required class="rounded border-gray-300 text-sm">@foreach(\Modules\Organization\Models\Branch::all() as $b)<option value="{{ $b->id }}">{{ $b->code }}</option>@endforeach</select>
                <input name="zone" placeholder="Delivery zone" class="rounded border-gray-300 text-sm">
                <select name="customer_id" class="rounded border-gray-300 text-sm"><option value="">Customer…</option>
                    @foreach(\Modules\Commercial\Models\Customer::limit(50)->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Create</button></form>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Batch</th><th>Customer</th><th>Zone</th><th>Packages</th><th>Status</th></tr></thead>
            <tbody class="divide-y">@forelse($batches as $b)
                <tr><td class="p-2 font-mono">{{ $b->reference }}</td><td>{{ $b->customer?->name ?? '—' }}</td><td>{{ $b->zone ?? '—' }}</td><td>{{ $b->items->count() }}</td><td><span class="text-xs px-2 rounded bg-indigo-100 text-indigo-700">{{ $b->status }}</span></td></tr>
                @empty<tr><td colspan="5" class="p-4 text-center text-gray-400">No batches.</td></tr>@endforelse
            </tbody></table>{{ $batches->links() }}</div>
    </div>
</x-app-layout>
