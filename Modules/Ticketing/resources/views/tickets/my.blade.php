<x-app-layout>
    <x-slot name="header">{{ __('My Tickets') }}</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr>
                <th class="px-4 py-3 text-left">Ticket</th><th class="px-4 py-3 text-left">Subject</th>
                <th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Updated</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($tickets as $t)
                    <tr><td class="px-4 py-3 font-mono text-xs"><a href="{{ route('ticketing.show', $t) }}" class="text-xerin-700 hover:underline">{{ $t->ticket_number }}</a></td>
                        <td class="px-4 py-3">{{ $t->subject }}</td>
                        <td class="px-4 py-3 text-xs">{{ ucwords(str_replace('_',' ',$t->status)) }}</td>
                        <td class="px-4 py-3 text-xs text-gray-400">{{ $t->updated_at->diffForHumans() }}</td></tr>
                @empty <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">You have no tickets. <a href="{{ route('ticketing.catalog') }}" class="text-xerin-700 underline">Browse services</a></td></tr> @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tickets->links() }}</div>
</x-app-layout>
