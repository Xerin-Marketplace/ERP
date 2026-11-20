<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Pricing Approvals</h2></x-slot>
    <form method="post" action="{{ route('sales.approvals.store') }}" class="bg-white rounded-lg shadow p-4 mb-4 grid md:grid-cols-5 gap-2">@csrf
        <select name="entity_type" class="border rounded px-2 py-1"><option>discount</option><option>special_rate</option><option>below_minimum</option><option>contract_exception</option><option>rate_override</option><option>credit_terms</option><option>low_margin</option></select>
        <input name="entity_ref" placeholder="Quotation/contract ref" class="border rounded px-2 py-1">
        <input name="original_value" type="number" step="0.01" placeholder="Original" class="border rounded px-2 py-1">
        <input name="requested_value" type="number" step="0.01" required placeholder="Requested" class="border rounded px-2 py-1">
        <input name="reason" required placeholder="Reason" class="border rounded px-2 py-1">
        <button class="bg-blue-600 text-white rounded px-3 py-1">Request</button>
    </form>
    <table class="w-full bg-white rounded-lg shadow text-sm">
        <tr class="text-left border-b"><th class="p-2">Ref</th><th>Type</th><th>Ref'd entity</th><th class="text-right">Orig→Req</th><th>Reason</th><th>Status</th><th></th></tr>
        @foreach($approvals as $a)<tr class="border-t"><td class="p-2 font-mono text-xs">{{ $a->reference }}</td><td>{{ $a->entity_type }}</td>
        <td class="text-xs">{{ $a->entity_ref ?? '—' }}</td><td class="text-right font-mono">{{ $a->original_value }}→{{ $a->requested_value }}</td>
        <td class="text-xs">{{ $a->reason }}</td><td>{{ $a->status }}</td>
        <td class="p-1">@if($a->status==='pending')<div class="flex gap-1">
            <form method="post" action="{{ route('sales.approvals.decide',$a) }}">@csrf<input type="hidden" name="decision" value="approved"><button class="text-xs bg-green-600 text-white rounded px-2">approve</button></form>
            <form method="post" action="{{ route('sales.approvals.decide',$a) }}">@csrf<input type="hidden" name="decision" value="rejected"><button class="text-xs bg-red-600 text-white rounded px-2">reject</button></form></div>@endif</td></tr>@endforeach
    </table>
</x-app-layout>
