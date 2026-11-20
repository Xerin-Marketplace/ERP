<x-app-layout>
    <x-slot name="header">Claims</x-slot>
    <div class="grid md:grid-cols-3 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-3">New Claim</h3>
            <form method="POST" action="{{ route('portal.claims.store') }}" class="space-y-3">@csrf
                <select name="type" class="w-full rounded border-gray-300 text-sm"><option value="lost">Lost cargo</option><option value="damaged">Damaged</option><option value="missing_items">Missing items</option><option value="partial_delivery">Partial delivery</option><option value="incorrect_delivery">Incorrect delivery</option></select>
                <input name="claimed_amount" required placeholder="Claimed amount (TZS)" class="w-full rounded border-gray-300 text-sm">
                <input name="awb" placeholder="AWB (optional)" class="w-full rounded border-gray-300 text-sm">
                <textarea name="description" required placeholder="Describe the claim" class="w-full rounded border-gray-300 text-sm" rows="3"></textarea>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Submit Claim</button></form></div>
        <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Ref</th><th>Type</th><th>Claimed</th><th>Approved</th><th>Status</th></tr></thead>
            <tbody class="divide-y">@forelse($claims as $c)
                <tr><td class="p-2 font-mono">{{ $c->reference }}</td><td class="capitalize">{{ str_replace('_',' ',$c->type) }}</td>
                    <td>{{ number_format($c->claimed_amount) }}</td><td>{{ $c->approved_amount ? number_format($c->approved_amount) : '—' }}</td>
                    <td><span class="text-xs px-2 rounded {{ in_array($c->status,['approved','settled'])?'bg-green-100 text-green-700':($c->status==='rejected'?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700') }}">{{ str_replace('_',' ',$c->status) }}</span></td></tr>
                @empty<tr><td colspan="5" class="p-4 text-center text-gray-400">No claims.</td></tr>@endforelse
            </tbody></table>{{ $claims->links() }}</div>
    </div>
</x-app-layout>
