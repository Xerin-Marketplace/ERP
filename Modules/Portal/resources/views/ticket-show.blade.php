<x-app-layout>
    <x-slot name="header">Ticket {{ $t->ticket_number }}</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-5 max-w-3xl space-y-4">
        <div><h3 class="font-semibold">{{ $t->subject }}</h3><p class="text-sm text-gray-600 mt-1">{{ $t->description }}</p></div>
        <div class="border-t pt-3 space-y-3">
            @foreach($t->comments as $m)
            <div class="text-sm"><span class="text-xs text-gray-400">{{ $m->created_at->format('d M H:i') }} · {{ $m->author_id ? 'Staff' : 'You' }}</span><p>{{ $m->body }}</p></div>
            @endforeach
        </div>
        <form method="POST" action="{{ route('portal.tickets.reply', $t) }}" class="flex gap-2 border-t pt-3">@csrf
            <input name="body" required placeholder="Reply…" class="rounded border-gray-300 text-sm flex-1">
            <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Send</button></form>
    </div>
</x-app-layout>
