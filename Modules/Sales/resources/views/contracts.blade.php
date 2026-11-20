<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Commercial Contracts</h2></x-slot>
    <form method="post" action="{{ route('sales.contracts.store') }}" class="bg-white rounded-lg shadow p-4 mb-4 grid md:grid-cols-5 gap-2">@csrf
        <select name="type" class="border rounded px-2 py-1"><option>corporate</option><option>sme</option><option>agent</option><option>broker</option><option>carrier</option><option>special_rate</option><option>partnership</option></select>
        <input name="customer_id" placeholder="Customer ID" class="border rounded px-2 py-1">
        <input name="partner_id" placeholder="Partner ID" class="border rounded px-2 py-1">
        <input name="start_date" type="date" class="border rounded px-2 py-1"><input name="expiry_date" type="date" class="border rounded px-2 py-1">
        <button class="bg-blue-600 text-white rounded px-3 py-1">Draft contract</button>
    </form>
    <table class="w-full bg-white rounded-lg shadow text-sm">
        <tr class="text-left border-b"><th class="p-2">Ref</th><th>Type</th><th>Customer/Partner</th><th>Period</th><th>Status</th><th></th></tr>
        @foreach($contracts as $c)<tr class="border-t"><td class="p-2 font-mono"><a class="text-blue-600" href="{{ route('sales.contracts.show',$c) }}">{{ $c->reference }}</a></td>
        <td>{{ $c->type }}</td><td>{{ $c->customer?->name ?? $c->partner?->name ?? '—' }}</td><td class="text-xs">{{ $c->start_date?->format('Y-m-d') }} → {{ $c->expiry_date?->format('Y-m-d') }}</td>
        <td><span class="px-2 py-0.5 rounded text-xs bg-{{ $c->status==='active'?'green':'gray' }}-100">{{ $c->status }}</span></td><td class="p-2 text-xs">v{{ $c->version }}</td></tr>@endforeach
    </table>
</x-app-layout>
