<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Contract {{ $c->reference }}</h2></x-slot>
    <div class="grid md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg shadow p-4 md:col-span-2">
            <div class="text-sm space-y-1">
                <div><b>Type:</b> {{ $c->type }} · <b>Status:</b> {{ $c->status }} · <b>v{{ $c->version }}</b></div>
                <div><b>Party:</b> {{ $c->customer?->name ?? $c->partner?->name ?? '—' }}</div>
                <div><b>Period:</b> {{ $c->start_date?->format('Y-m-d') }} → {{ $c->expiry_date?->format('Y-m-d') }}
                    @if($c->isExpired() && $c->status==='active') <span class="text-red-600 font-semibold">EXPIRED</span>@endif</div>
                <div><b>Signature evidence:</b> {{ $c->signature_evidence ?? 'none — cannot activate' }}</div>
                <pre class="text-xs bg-gray-50 rounded p-2 mt-2">{{ json_encode($c->terms, JSON_PRETTY_PRINT) }}</pre>
            </div>
            <div class="mt-3 flex gap-2 flex-wrap">
                @foreach(['under_review','pending_approval','approved','pending_signature'] as $to)
                <form method="post" action="{{ route('sales.contracts.transition',$c) }}">@csrf<input type="hidden" name="to" value="{{ $to }}"><button class="text-xs bg-gray-100 rounded px-2 py-1">{{ $to }}</button></form>
                @endforeach
                <form method="post" action="{{ route('sales.contracts.transition',$c) }}" class="flex gap-1">@csrf<input type="hidden" name="to" value="active"><input name="signature_evidence" placeholder="signature evidence ref" class="border rounded px-1 text-xs"><button class="text-xs bg-green-600 text-white rounded px-2">activate</button></form>
                @foreach(['suspended','terminated','expired','renewed'] as $to)
                <form method="post" action="{{ route('sales.contracts.transition',$c) }}">@csrf<input type="hidden" name="to" value="{{ $to }}"><button class="text-xs bg-red-50 text-red-700 rounded px-2 py-1">{{ $to }}</button></form>
                @endforeach
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold text-sm mb-2">Version history</h3>
            @foreach($c->versions->sortByDesc('version') as $v)
            <div class="border-t py-1 text-xs"><b>v{{ $v->version }}</b> · {{ $v->status }} · {{ $v->created_at->format('Y-m-d') }}</div>
            @endforeach
        </div>
    </div>
</x-app-layout>
