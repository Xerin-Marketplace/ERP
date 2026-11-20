<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Commercial Campaigns</h2></x-slot>
    <form method="post" action="{{ route('sales.campaigns.store') }}" class="bg-white rounded-lg shadow p-4 mb-4 grid md:grid-cols-6 gap-2">@csrf
        <input name="name" required placeholder="Name" class="border rounded px-2 py-1">
        <select name="type" class="border rounded px-2 py-1"><option>discount</option><option>acquisition</option><option>seasonal</option><option>corporate</option><option>referral</option><option>agent</option><option>service</option><option>route</option></select>
        <input name="start_date" type="date" required class="border rounded px-2 py-1"><input name="end_date" type="date" required class="border rounded px-2 py-1">
        <select name="discount_type" class="border rounded px-2 py-1"><option value="">no discount</option><option>percent</option><option>fixed</option></select>
        <input name="discount_value" type="number" step="0.01" placeholder="Value" class="border rounded px-2 py-1">
        <input name="usage_limit" type="number" placeholder="Usage limit" class="border rounded px-2 py-1">
        <input name="segment_code" placeholder="Segment" class="border rounded px-2 py-1">
        <button class="bg-blue-600 text-white rounded px-3 py-1">Create</button>
    </form>
    <table class="w-full bg-white rounded-lg shadow text-sm">
        <tr class="text-left border-b"><th class="p-2">Ref</th><th>Name</th><th>Type</th><th>Period</th><th>Discount</th><th>Usage</th><th>Status</th></tr>
        @foreach($campaigns as $c)<tr class="border-t"><td class="p-2 font-mono text-xs">{{ $c->reference }}</td><td>{{ $c->name }}</td><td>{{ $c->type }}</td>
        <td class="text-xs">{{ $c->start_date->format('Y-m-d') }}→{{ $c->end_date->format('Y-m-d') }}</td>
        <td class="text-xs">{{ $c->discount_type }} {{ $c->discount_value }}</td><td>{{ $c->used_count }}/{{ $c->usage_limit ?? '∞' }}</td><td>{{ $c->status }}</td></tr>@endforeach
    </table>
</x-app-layout>
