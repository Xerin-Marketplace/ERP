<x-guest-layout>
    <div class="max-w-lg mx-auto py-12 px-4">
        <h1 class="text-xl font-bold">Track Access Request</h1>
        <form method="GET" class="mt-6 rounded-sm border border-gray-200 bg-white p-6 shadow-sm space-y-4">
            <div><label class="block text-xs font-semibold text-gray-600">Reference</label>
                <input name="reference" value="{{ request('reference') }}" placeholder="XER-ACC-2026-000001" class="mt-1 w-full rounded-sm border-gray-300 text-sm font-mono"></div>
            <div><label class="block text-xs font-semibold text-gray-600">Tracking Token</label>
                <input name="token" value="{{ request('token') }}" class="mt-1 w-full rounded-sm border-gray-300 text-sm font-mono"></div>
            <button class="rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Check Status</button>
        </form>
        @if ($searched)
            @if ($found)
                <div class="mt-6 rounded-sm border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex justify-between"><span class="font-mono font-bold">{{ $found->reference }}</span>
                        <span class="rounded-sm bg-xerin-50 px-2 py-0.5 text-xs font-semibold text-xerin-700">{{ ucfirst(str_replace('_',' ',$found->status)) }}</span></div>
                    <div class="mt-4 space-y-2">
                        @foreach ($found->history()->where('internal', false)->latest()->get() as $h)
                            <div class="flex gap-3 text-xs"><span class="text-gray-400">{{ $h->created_at->format('d M H:i') }}</span>
                                <span>{{ str_replace('.', ' — ', $h->event) }}</span></div>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="mt-4 text-sm text-red-600">No request found — check the reference and token.</p>
            @endif
        @endif
    </div>
</x-guest-layout>
