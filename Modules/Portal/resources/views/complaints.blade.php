<x-app-layout>
    <x-slot name="header">Complaints</x-slot>
    <div class="grid md:grid-cols-3 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-3">New Complaint</h3>
            <form method="POST" action="{{ route('portal.complaints.store') }}" class="space-y-3">@csrf
                <select name="category" class="w-full rounded border-gray-300 text-sm"><option value="cargo_delay">Cargo delay</option><option value="cargo_damage">Damage</option><option value="missing_cargo">Missing cargo</option><option value="delivery_failure">Delivery failure</option><option value="customer_service">Service</option><option value="billing_dispute">Billing</option><option value="other">Other</option></select>
                <textarea name="description" required placeholder="Details" class="w-full rounded border-gray-300 text-sm" rows="4"></textarea>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Submit</button></form></div>
        <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Ref</th><th>Category</th><th>Status</th><th>Date</th></tr></thead>
            <tbody class="divide-y">@forelse($complaints as $c)
                <tr><td class="p-2 font-mono">{{ $c->reference }}</td><td class="capitalize">{{ str_replace('_',' ',$c->category) }}</td>
                    <td><span class="text-xs px-2 rounded bg-amber-100 text-amber-700">{{ str_replace('_',' ',$c->status) }}</span></td><td>{{ $c->created_at->format('d M') }}</td></tr>
                @empty<tr><td colspan="4" class="p-4 text-center text-gray-400">No complaints.</td></tr>@endforelse
            </tbody></table>{{ $complaints->links() }}</div>
    </div>
</x-app-layout>
