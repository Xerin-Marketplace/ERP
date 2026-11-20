<x-app-layout>
    <x-slot name="header">Privacy Requests</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Ref</th><th>Customer</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
        <tbody class="divide-y">@foreach($requests as $p)
            <tr><td class="p-2 font-mono">{{ $p->reference }}</td><td>{{ $p->customer?->name }}</td><td>{{ $p->type }}</td>
                <td><span class="text-xs px-2 rounded bg-amber-100 text-amber-700">{{ $p->status }}</span></td><td>{{ $p->created_at->format('d M') }}</td></tr>
            @endforeach</tbody></table>{{ $requests->links() }}</div>
</x-app-layout>
