<x-app-layout>
    <x-slot name="header">Customer Tickets</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
        <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Ref</th><th>Customer</th><th>Subject</th><th>Status</th><th>Reply</th></tr></thead>
        <tbody class="divide-y">@foreach($tickets as $t)
            <tr><td class="p-2 font-mono">{{ $t->ticket_number }}</td><td>{{ $t->customer?->name }}</td><td>{{ $t->subject }}</td>
                <td><span class="text-xs px-2 rounded bg-indigo-100 text-indigo-700">{{ $t->status }}</span></td>
                <td><form method="POST" action="{{ route('desk.tickets.reply', $t) }}" class="flex gap-1">@csrf
                    <input name="body" placeholder="Reply" class="rounded border-gray-300 text-xs w-32">
                    <label class="text-xs"><input type="checkbox" name="internal" value="1">internal</label>
                    <button class="px-2 py-0.5 bg-indigo-600 text-white rounded text-xs">Send</button></form></td></tr>
            @endforeach</tbody></table>{{ $tickets->links() }}</div>
</x-app-layout>
