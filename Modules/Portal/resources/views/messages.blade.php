<x-app-layout>
    <x-slot name="header">Communications</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-5 space-y-2">
        @forelse($messages as $m)
        <div class="border rounded p-3 text-sm"><div class="flex justify-between text-xs text-gray-400"><span>{{ strtoupper($m->channel) }} · {{ $m->direction }}</span><span>{{ $m->created_at->format('d M H:i') }} · {{ $m->status }}</span></div>
            <p class="mt-1">{{ $m->body }}</p></div>
        @empty<p class="text-center text-gray-400 py-6">No messages.</p>@endforelse
        {{ $messages->links() }}
    </div>
</x-app-layout>
