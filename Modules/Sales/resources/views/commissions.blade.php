<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Commissions</h2></x-slot>
    <details class="bg-white rounded-lg shadow p-4 mb-4"><summary class="font-semibold cursor-pointer">+ Commission rule</summary>
        <form method="post" action="{{ route('sales.commission-rules.store') }}" class="grid md:grid-cols-6 gap-2 mt-3">@csrf
            <select name="beneficiary_type" class="border rounded px-2 py-1"><option>agent</option><option>broker</option><option>officer</option></select>
            <input name="beneficiary_id" type="number" required placeholder="Beneficiary ID" class="border rounded px-2 py-1">
            <select name="basis" class="border rounded px-2 py-1"><option>revenue_pct</option><option>per_shipment</option><option>per_acquisition</option><option>tiered</option></select>
            <input name="rate" type="number" step="0.0001" placeholder="Rate (0.05=5%)" class="border rounded px-2 py-1">
            <input name="fixed_amount" type="number" step="0.01" placeholder="Fixed" class="border rounded px-2 py-1">
            <input name="partner_id" type="number" placeholder="Partner ID" class="border rounded px-2 py-1">
            <button class="bg-blue-600 text-white rounded px-3 py-1">Save rule</button>
        </form></details>
    <table class="w-full bg-white rounded-lg shadow text-sm">
        <tr class="text-left border-b"><th class="p-2">Ref</th><th>Beneficiary</th><th>Source</th><th class="text-right">Basis</th><th class="text-right">Commission</th><th>Status</th><th>Settlement</th><th></th></tr>
        @foreach($records as $r)<tr class="border-t"><td class="p-2 font-mono text-xs">{{ $r->reference }}</td>
        <td>{{ $r->beneficiary_type }} #{{ $r->beneficiary_id }}</td><td class="text-xs">{{ $r->source_type }}#{{ $r->source_id }}</td>
        <td class="text-right font-mono">{{ number_format($r->basis_amount,2) }}</td><td class="text-right font-mono">{{ number_format($r->commission_amount,2) }}</td>
        <td><span class="px-2 py-0.5 rounded text-xs bg-{{ $r->status==='paid'?'green':($r->status==='rejected'?'red':'gray') }}-100">{{ $r->status }}</span></td>
        <td class="text-xs">{{ $r->settlement_ref ?? '—' }}</td>
        <td class="p-1"><div class="flex gap-1 flex-wrap">
            @foreach(['under_review','approved','payable','rejected','disputed','reversed'] as $to)
            <form method="post" action="{{ route('sales.commissions.transition',$r) }}">@csrf<input type="hidden" name="to" value="{{ $to }}"><button class="text-xs bg-gray-100 rounded px-1">{{ $to }}</button></form>
            @endforeach
            <form method="post" action="{{ route('sales.commissions.transition',$r) }}" class="flex gap-1">@csrf<input type="hidden" name="to" value="paid"><input name="settlement_ref" placeholder="fin ref" class="border rounded px-1 text-xs w-16"><button class="text-xs bg-green-600 text-white rounded px-1">pay</button></form>
        </div></td></tr>@endforeach
    </table>
</x-app-layout>
