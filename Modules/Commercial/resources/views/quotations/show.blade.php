<x-app-layout>
    <x-slot name="header">Quotation {{ $quotation->number }}</x-slot>
    <div class="space-y-6">
        <div class="bg-white rounded-md border border-gray-200 p-5 flex flex-wrap justify-between gap-4">
            <div>
                <div class="flex items-center gap-3"><h2 class="text-lg font-semibold">{{ $quotation->number }} <span class="text-sm text-gray-400">v{{ $quotation->version }}</span></h2><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ str_replace('_',' ',$quotation->status) }}</span></div>
                <div class="mt-1 text-sm text-gray-600">{{ $quotation->customer?->name ?? '—' }} · {{ $quotation->branch?->name ?? 'No branch' }}@if($quotation->inquiry) · Inquiry {{ $quotation->inquiry->reference }}@endif</div>
                <div class="mt-1 text-sm">{{ $quotation->origin ?? '?' }} → {{ $quotation->destination ?? '?' }} · Chargeable {{ $quotation->chargeable_weight ?? '—' }} kg</div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('commercial.quotations.print', $quotation) }}" target="_blank" class="px-4 py-2 border border-gray-200 rounded-sm text-sm">Print / PDF</a>
                @if($quotation->isOpen() || $quotation->status === 'approved')<a href="{{ url('/q/'.$quotation->acceptance_token) }}" target="_blank" class="px-4 py-2 border border-xerin-200 text-xerin-700 rounded-sm text-sm">Customer link</a>@endif
            </div>
        </div>
        @can('quotation.update')
        @if(count(\Modules\Commercial\Models\Quotation::TRANSITIONS[$quotation->status] ?? []))
        <div class="bg-white rounded-md border border-gray-200 p-4 flex flex-wrap gap-2">
            @foreach(\Modules\Commercial\Models\Quotation::TRANSITIONS[$quotation->status] as $s)
                @continue(in_array($s,['accepted','rejected','viewed','superseded']))
                <form method="POST" action="{{ route('commercial.quotations.transition', $quotation) }}" onsubmit="return confirm('Move to {{ $s }}?')">@csrf<input type="hidden" name="status" value="{{ $s }}"><button class="px-4 py-2 rounded-sm text-sm {{ $s==='sent'||$s==='approved'?'bg-xerin-700 text-white':'border border-gray-200' }}">{{ ucfirst(str_replace('_',' ',$s)) }}</button></form>
            @endforeach
        </div>
        @endif
        @endcan
        <div class="grid lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 bg-white rounded-md border border-gray-200">
                <div class="px-5 py-3 border-b border-gray-100 font-semibold">Pricing Breakdown</div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100">
                        @forelse($quotation->items as $item)
                            <tr><td class="px-5 py-2.5">{{ $item->label }}</td><td class="px-5 py-2.5 text-right {{ $item->kind==='discount'?'text-emerald-700':'' }}">{{ $item->kind==='discount'?'−':'' }}{{ number_format($item->amount,2) }} {{ $quotation->currency }}</td></tr>
                        @empty <tr><td class="px-5 py-4 text-gray-400" colspan="2">No priced items.</td></tr> @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 font-semibold"><tr><td class="px-5 py-3">Total</td><td class="px-5 py-3 text-right">{{ number_format($quotation->total,2) }} {{ $quotation->currency }}</td></tr></tfoot>
                </table>
                @if($quotation->pricing_snapshot)<div class="px-5 py-3 text-xs text-gray-400 border-t border-gray-100">Snapshot: {{ $quotation->pricing_snapshot['rate_card']['code'] ?? 'manual' }} · {{ $quotation->pricing_snapshot['computed_at'] ?? '' }}</div>@endif
            </div>
            <div class="space-y-6">
                <div class="bg-white rounded-md border border-gray-200 p-5 text-sm space-y-1.5">
                    <h3 class="font-semibold">Details</h3>
                    <div>Valid until: {{ $quotation->valid_until?->format('d M Y') ?? '—' }}</div>
                    <div>Prepared by: {{ $quotation->preparer?->name ?? '—' }}</div>
                    @if($quotation->approver)<div>Approved by: {{ $quotation->approver->name }}</div>@endif
                    @if($quotation->payment_terms)<div class="text-gray-600">{{ $quotation->payment_terms }}</div>@endif
                </div>
                @if($quotation->handover)
                <div class="bg-emerald-50 border border-emerald-200 rounded-md p-4 text-sm"><strong>Handover:</strong> {{ $quotation->handover->reference }}<div class="text-xs text-gray-600 mt-1">Commercial handover to Cargo Operations recorded.</div></div>
                @endif
            </div>
        </div>
        <div class="bg-white rounded-md border border-gray-200">
            <div class="px-5 py-3 border-b border-gray-100 font-semibold">Event History</div>
            <div class="divide-y divide-gray-100">
                @forelse($quotation->events as $e)
                    <div class="px-5 py-2.5 text-sm flex justify-between"><span class="capitalize">{{ str_replace('_',' ',$e->event) }}</span><span class="text-xs text-gray-400">{{ $e->user?->name ?? 'system' }} · {{ $e->created_at->diffForHumans() }}</span></div>
                @empty <p class="px-5 py-4 text-sm text-gray-400">No events.</p> @endforelse
            </div>
        </div>
        @if($quotation->parent || $quotation->versions->isNotEmpty())
        <div class="bg-white rounded-md border border-gray-200 p-5 text-sm">
            <h3 class="font-semibold mb-2">Versions</h3>
            @if($quotation->parent)<div>← <a class="text-xerin-700 hover:underline" href="{{ route('commercial.quotations.show', $quotation->parent) }}">{{ $quotation->parent->number }}</a></div>@endif
            @foreach($quotation->versions as $v)<div>→ <a class="text-xerin-700 hover:underline" href="{{ route('commercial.quotations.show', $v) }}">{{ $v->number }} (v{{ $v->version }}, {{ $v->status }})</a></div>@endforeach
        </div>
        @endif
    </div>
</x-app-layout>
