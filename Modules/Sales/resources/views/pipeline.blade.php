<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Sales Pipeline</h2></x-slot>
    <details class="bg-white rounded-lg shadow p-4 mb-4"><summary class="font-semibold cursor-pointer">+ New Opportunity</summary>
        <form method="post" action="{{ route('sales.opportunities.store') }}" class="grid md:grid-cols-4 gap-2 mt-3">@csrf
            <input name="name" required placeholder="Opportunity name" class="border rounded px-2 py-1">
            <input name="customer_id" placeholder="Customer ID" class="border rounded px-2 py-1">
            <input name="expected_revenue" type="number" step="0.01" placeholder="Expected revenue" class="border rounded px-2 py-1">
            <input name="expected_close" type="date" class="border rounded px-2 py-1">
            <input name="service_category" placeholder="Service" class="border rounded px-2 py-1">
            <input name="competitor" placeholder="Competitor" class="border rounded px-2 py-1">
            <button class="bg-blue-600 text-white rounded px-3 py-1">Create</button>
        </form></details>
    <div class="flex gap-3 overflow-x-auto pb-2">
        @foreach($stages as $s)
        <div class="min-w-[240px] bg-gray-50 rounded-lg p-3">
            <div class="font-semibold text-sm">{{ $s->name }} <span class="text-gray-400">({{ $s->probability }}%)</span></div>
            @foreach($opps->get($s->id, collect()) as $o)
            <div class="bg-white rounded shadow-sm p-2 mt-2 text-sm">
                <div class="font-medium">{{ $o->reference }} · {{ $o->name }}</div>
                <div class="text-xs text-gray-500">{{ $o->customer?->name ?? $o->lead?->name ?? '—' }} · {{ number_format($o->expected_revenue ?? 0) }}</div>
                <div class="flex gap-1 mt-1">
                    @foreach($stages->where('id','!=',$s->id) as $ns)
                    <form method="post" action="{{ route('sales.opportunities.move',$o) }}">@csrf<input type="hidden" name="stage_id" value="{{ $ns->id }}"><button class="text-xs bg-gray-100 rounded px-1">→{{ $ns->code }}</button></form>
                    @endforeach
                </div>
                <div class="flex gap-1 mt-1">
                    <form method="post" action="{{ route('sales.opportunities.close',$o) }}">@csrf<input type="hidden" name="status" value="won"><button class="text-xs text-green-700">won</button></form>
                    <form method="post" action="{{ route('sales.opportunities.close',$o) }}">@csrf<input type="hidden" name="status" value="lost"><input type="hidden" name="lost_reason" value="lost"><button class="text-xs text-red-600">lost</button></form>
                </div>
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
</x-app-layout>
