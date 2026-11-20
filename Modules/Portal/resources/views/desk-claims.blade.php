<x-app-layout>
    <x-slot name="header">Claims Register</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Ref</th><th>Customer</th><th>Type</th><th>Claimed</th><th>Status</th><th></th></tr></thead>
        <tbody class="divide-y">@foreach($claims as $c)
            <tr><td class="p-2"><a href="{{ route('desk.claims.show', $c) }}" class="text-indigo-600 font-mono">{{ $c->reference }}</a></td>
                <td>{{ $c->customer?->name }}</td><td class="capitalize">{{ str_replace('_',' ',$c->type) }}</td>
                <td>{{ number_format($c->claimed_amount) }}</td><td><span class="text-xs px-2 rounded bg-amber-100 text-amber-700">{{ str_replace('_',' ',$c->status) }}</span></td>
                <td><a href="{{ route('desk.claims.show', $c) }}" class="text-xs text-indigo-600">Open</a></td></tr>
            @endforeach</tbody></table>{{ $claims->links() }}</div>
</x-app-layout>
