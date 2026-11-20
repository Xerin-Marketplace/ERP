<x-app-layout>
    <x-slot name="header">Support Tickets</x-slot>
    <div class="grid md:grid-cols-3 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-3">New Ticket</h3>
            <form method="POST" action="{{ route('portal.tickets.store') }}" class="space-y-3">@csrf
                <input name="subject" required placeholder="Subject" class="w-full rounded border-gray-300 text-sm">
                <select name="priority" class="w-full rounded border-gray-300 text-sm"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select>
                <textarea name="description" required placeholder="Describe the issue" class="w-full rounded border-gray-300 text-sm" rows="4"></textarea>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Submit</button></form></div>
        <div class="md:col-span-2 bg-white rounded-lg shadow-sm p-5"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Ref</th><th>Subject</th><th>Status</th><th>Created</th></tr></thead>
            <tbody class="divide-y">@forelse($tickets as $t)
                <tr><td class="p-2"><a href="{{ route('portal.tickets.show', $t) }}" class="text-indigo-600 font-mono">{{ $t->ticket_number }}</a></td>
                    <td>{{ $t->subject }}</td><td><span class="text-xs px-2 rounded bg-indigo-100 text-indigo-700">{{ $t->status }}</span></td><td>{{ $t->created_at->format('d M') }}</td></tr>
                @empty<tr><td colspan="4" class="p-4 text-center text-gray-400">No tickets.</td></tr>@endforelse
            </tbody></table>{{ $tickets->links() }}</div>
    </div>
</x-app-layout>
