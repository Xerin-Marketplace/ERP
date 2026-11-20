<x-app-layout>
    <x-slot name="header">{{ $t->title }}</x-slot>
    <div class="grid md:grid-cols-3 gap-4 mb-4">
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-xs text-gray-500">Ref / Buyer</div><div class="font-mono">{{ $t->reference }}</div><div class="text-sm">{{ $t->buyer_name }} ({{ $t->buyer_type }})</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-xs text-gray-500">Stage</div><div class="font-semibold capitalize">{{ str_replace('_',' ',$t->stage) }}</div>
            <div class="text-xs text-gray-500 mt-1">Deadline: {{ $t->submission_deadline?->format('d M Y H:i') ?? '—' }}</div></div>
        <div class="rounded-lg bg-white p-4 shadow"><div class="text-xs text-gray-500">Est. / Submitted</div>
            <div>{{ $t->currency }} {{ number_format($t->estimated_value ?? 0) }} / {{ number_format($t->submitted_value ?? 0) }}</div></div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div class="rounded-lg bg-white shadow p-4">
            <h3 class="font-semibold mb-2">Bid / No-Bid</h3>
            @if ($t->assessment)
                <div class="text-sm mb-2">Score: <b>{{ $t->assessment->total_score }}</b>/100 — Decision: <b class="uppercase">{{ $t->assessment->decision }}</b></div>
            @endif
            @if (! $t->assessment || $t->assessment->decision === 'pending_review')
            <form method="POST" action="{{ route('tender.assess',$t) }}" class="grid grid-cols-5 gap-2 mb-2">@csrf
                @foreach (['commercial','technical','compliance','strategic','risk'] as $k)
                <input name="scores[{{ $k }}][score]" type="number" min="0" max="100" placeholder="{{ $k }}" class="rounded border-gray-300 text-xs" required>@endforeach
                <button class="col-span-5 rounded bg-indigo-600 text-white text-xs py-1.5">Assess</button></form>
            <form method="POST" action="{{ route('tender.decide',$t) }}" class="flex gap-2">@csrf
                <select name="decision" class="rounded border-gray-300 text-xs"><option value="bid">BID</option><option value="conditional_bid">CONDITIONAL</option><option value="no_bid">NO_BID</option></select>
                <input name="reason" placeholder="reason (required for NO_BID)" class="flex-1 rounded border-gray-300 text-xs">
                <button class="rounded bg-green-600 text-white text-xs px-3">Decide</button></form>
            @endif
        </div>

        <div class="rounded-lg bg-white shadow p-4">
            <h3 class="font-semibold mb-2">Submission Readiness</h3>
            @foreach ($checklist as $c)
            <div class="flex justify-between border-t py-1 text-xs"><span>{{ $c['item'] }}</span><span class="{{ $c['ok']?'text-green-600':'text-red-600' }}">{{ $c['ok']?'OK':$c['detail'] }}</span></div>
            @endforeach
            <form method="POST" action="{{ route('tender.submit',$t) }}" class="flex gap-2 mt-3">@csrf
                <select name="method" class="rounded border-gray-300 text-xs"><option value="nest">NeST</option><option value="portal">Portal</option><option value="physical">Physical</option><option value="email">Email</option></select>
                <input name="platform_ref" placeholder="ref" class="rounded border-gray-300 text-xs">
                <input name="evidence_document_id" placeholder="evidence doc id" class="w-28 rounded border-gray-300 text-xs" required>
                <button class="rounded bg-indigo-600 text-white text-xs px-3">Submit</button></form>
        </div>

        <div class="rounded-lg bg-white shadow p-4">
            <h3 class="font-semibold mb-2">BOQ {{ $t->activeBoq ? "(v{$t->activeBoq->version})" : '' }}</h3>
            @if ($boqTotals)
            <div class="text-xs grid grid-cols-2 gap-1 mb-2">
                <span>Direct: {{ number_format($boqTotals['direct'],2) }}</span><span>Overhead: {{ number_format($boqTotals['overhead'],2) }}</span>
                <span>Profit: {{ number_format($boqTotals['profit'],2) }}</span><span>Tax: {{ number_format($boqTotals['tax'],2) }}</span>
                <span class="col-span-2 font-bold">Grand total: {{ number_format($boqTotals['grand_total'],2) }} (margin {{ $boqTotals['margin_pct'] }}%)</span></div>
            @endif
            @foreach ($t->activeBoq?->items ?? [] as $i)
            <div class="border-t py-1 text-xs">{{ $i->item_no }} — {{ Str::limit($i->description,40) }} <span class="text-gray-400">×{{ $i->quantity }}</span></div>
            @endforeach
            <form method="POST" action="{{ route('tender.boq.add',$t) }}" class="grid grid-cols-3 gap-1 mt-2">@csrf
                <input name="item_no" placeholder="item #" class="rounded border-gray-300 text-xs" required>
                <input name="description" placeholder="description" class="col-span-2 rounded border-gray-300 text-xs" required>
                <input name="quantity" type="number" step="0.0001" placeholder="qty" class="rounded border-gray-300 text-xs" required>
                <input name="material_cost" type="number" step="0.0001" placeholder="material" class="rounded border-gray-300 text-xs">
                <input name="labour_cost" type="number" step="0.0001" placeholder="labour" class="rounded border-gray-300 text-xs">
                <input name="unit_rate" type="number" step="0.0001" placeholder="unit rate (opt)" class="rounded border-gray-300 text-xs">
                <button class="col-span-2 rounded bg-indigo-600 text-white text-xs py-1">Add item</button></form>
        </div>

        <div class="rounded-lg bg-white shadow p-4">
            <h3 class="font-semibold mb-2">Evaluation Events & Award</h3>
            @foreach ($t->events->sortByDesc('event_date') as $e)
            <div class="border-t py-1 text-xs"><b>{{ $e->type }}</b> — {{ $e->event_date->format('d M') }} {{ Str::limit($e->note,50) }}</div>
            @endforeach
            <form method="POST" action="{{ route('tender.event',$t) }}" class="flex gap-1 mt-2">@csrf
                <select name="type" class="rounded border-gray-300 text-xs"><option value="opened">Opened</option><option value="under_eval">Under eval</option><option value="clarification">Clarification</option><option value="negotiation">Negotiation</option><option value="unsuccessful">Unsuccessful</option></select>
                <input name="event_date" type="date" class="rounded border-gray-300 text-xs" required>
                <button class="rounded bg-gray-600 text-white text-xs px-2">Log</button></form>
            @if ($t->stage === 'evaluation')
            <form method="POST" action="{{ route('tender.award',$t) }}" class="flex gap-1 mt-2">@csrf
                <input name="contract_ref" placeholder="contract ref" class="rounded border-gray-300 text-xs" required>
                <input name="value" type="number" step="0.01" placeholder="award value" class="rounded border-gray-300 text-xs" required>
                <button class="rounded bg-green-600 text-white text-xs px-2">Record award</button></form>
            @endif
            @foreach ($t->contracts as $c)<div class="border-t py-1 text-xs mt-1"><b>{{ $c->contract_ref }}</b> — {{ $t->currency }} {{ number_format($c->value) }} ({{ $c->status }})</div>@endforeach
        </div>
    </div>
</x-app-layout>
