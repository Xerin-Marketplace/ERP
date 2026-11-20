<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Sales Targets</h2></x-slot>
    <form method="post" action="{{ route('sales.targets.store') }}" class="bg-white rounded-lg shadow p-4 mb-4 grid md:grid-cols-6 gap-2">@csrf
        <select name="scope" class="border rounded px-2 py-1"><option>company</option><option>branch</option><option>officer</option><option>agent</option></select>
        <input name="scope_id" placeholder="ID (branch/officer)" class="border rounded px-2 py-1">
        <select name="period_type" class="border rounded px-2 py-1"><option>monthly</option><option>quarterly</option><option>annual</option></select>
        <input name="period" placeholder="2026-01 / 2026-Q1 / 2026" required class="border rounded px-2 py-1">
        <select name="metric" class="border rounded px-2 py-1"><option>revenue</option><option>new_customers</option><option>shipments</option><option>contracts</option></select>
        <input name="target" type="number" step="0.01" required placeholder="Target" class="border rounded px-2 py-1">
        <button class="bg-blue-600 text-white rounded px-3 py-1 md:col-span-6 w-32">Save target</button>
    </form>
    <table class="w-full bg-white rounded-lg shadow text-sm">
        <tr class="text-left border-b"><th class="p-2">Scope</th><th>Period</th><th>Metric</th><th class="text-right p-2">Target</th><th class="text-right">Actual</th><th class="text-right">%</th><th class="text-right p-2">Variance</th></tr>
        @foreach($targets as $row)<tr class="border-t"><td class="p-2">{{ $row['t']->scope }} #{{ $row['t']->scope_id ?? '—' }}</td><td>{{ $row['t']->period }}</td><td>{{ $row['t']->metric }}</td>
        <td class="text-right p-2 font-mono">{{ number_format($row['a']['target']) }}</td><td class="text-right font-mono">{{ number_format($row['a']['actual']) }}</td>
        <td class="text-right font-mono {{ $row['a']['pct']>=100?'text-green-700':'text-amber-600' }}">{{ $row['a']['pct'] ?? '—' }}%</td><td class="text-right p-2 font-mono">{{ number_format($row['a']['variance']) }}</td></tr>@endforeach
    </table>
</x-app-layout>
