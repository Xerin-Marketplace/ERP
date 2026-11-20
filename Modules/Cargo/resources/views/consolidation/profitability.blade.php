<x-app-layout>
    <x-slot name="header">Profitability — Group View</x-slot>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        @php $cards = [
            ['Packages', $group['packages'], 'gray'], ['Revenue', number_format($group['revenue']), 'blue'],
            ['Costs', number_format($group['costs']), 'amber'],
            ['Profit', number_format($group['profit']), $group['profit'] >= 0 ? 'green' : 'red'],
            ['Margin', ($group['margin_pct'] ?? 0).'%', 'xerin'],
        ]; @endphp
        @foreach ($cards as [$label, $value, $color])
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs text-gray-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-bold text-{{ $color }}-600">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        @foreach ($branches as $item)
        <div class="rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold">{{ $item['branch']->name }}</h3>
            <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
                <div><dt class="text-gray-400">Revenue</dt><dd class="font-semibold">{{ number_format($item['summary']['revenue']) }}</dd></div>
                <div><dt class="text-gray-400">Costs</dt><dd>{{ number_format($item['summary']['costs']) }}</dd></div>
                <div><dt class="text-gray-400">Profit</dt><dd class="font-bold {{ $item['summary']['profit'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ number_format($item['summary']['profit']) }}</dd></div>
                <div><dt class="text-gray-400">Margin</dt><dd>{{ $item['summary']['margin_pct'] ?? '—' }}%</dd></div>
            </dl>
        </div>
        @endforeach
    </div>

    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <div class="border-b border-gray-100 p-4"><h3 class="text-sm font-semibold">Recent Trips — Profit per Leg</h3></div>
        <table class="w-full text-sm">
            <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500">
                <th class="px-4 py-2">Trip</th><th class="px-4 py-2">Route</th><th class="px-4 py-2">Pkgs</th>
                <th class="px-4 py-2">Revenue</th><th class="px-4 py-2">Leg cost</th><th class="px-4 py-2">Profit</th><th class="px-4 py-2">Margin</th></tr></thead>
            <tbody>
            @forelse ($trips as $item)
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono text-xs">{{ $item['trip']->reference }}</td>
                    <td class="px-4 py-2 text-xs">{{ $item['trip']->originBranch->name ?? '' }} → {{ $item['trip']->destinationBranch->name ?? '' }}</td>
                    <td class="px-4 py-2">{{ $item['profit']['packages'] }}</td>
                    <td class="px-4 py-2">{{ number_format($item['profit']['revenue']) }}</td>
                    <td class="px-4 py-2">{{ number_format($item['profit']['leg_cost']) }}</td>
                    <td class="px-4 py-2 font-semibold {{ $item['profit']['profit'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ number_format($item['profit']['profit']) }}</td>
                    <td class="px-4 py-2 text-xs">{{ $item['profit']['margin_pct'] ?? '—' }}%</td>
                </tr>
            @empty <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">No trips yet.</td></tr> @endforelse
            </tbody></table>
    </div>

    <div class="mt-6 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <div class="border-b border-gray-100 p-4 flex items-center justify-between">
            <h3 class="text-sm font-semibold">Package Drill-Down — worst margins first</h3>
            <form method="GET" class="flex items-end gap-2">
                <select name="branch" class="rounded-sm border-gray-300 text-xs">
                    <option value="">All branches</option>
                    @foreach ($branchOptions as $b)
                        <option value="{{ $b->id }}" @selected(request('branch') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="rounded-sm border-gray-300 text-xs">
                <input type="date" name="to" value="{{ request('to') }}" class="rounded-sm border-gray-300 text-xs">
                <button class="rounded-sm bg-xerin-700 px-3 py-1.5 text-xs font-semibold text-white">Filter</button>
            </form>
        </div>
        <table class="w-full text-sm">
            <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500">
                <th class="px-4 py-2">Tracking</th><th class="px-4 py-2">Customer</th><th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Revenue</th><th class="px-4 py-2">Leg cost</th><th class="px-4 py-2">Delivery</th>
                <th class="px-4 py-2">Profit</th><th class="px-4 py-2">Margin</th><th class="px-4 py-2"></th></tr></thead>
            <tbody>
            @forelse ($packages as $row)
                <tr class="border-b border-gray-50 {{ $row['loss'] ? 'bg-red-50/40' : '' }}">
                    <td class="px-4 py-2 font-mono text-xs">{{ $row['package']->tracking_no ?? $row['package']->reference }}</td>
                    <td class="px-4 py-2 text-xs">{{ $row['package']->customer->name ?? '—' }}</td>
                    <td class="px-4 py-2 text-xs">{{ $row['package']->status }}</td>
                    <td class="px-4 py-2">{{ number_format($row['revenue']) }}</td>
                    <td class="px-4 py-2">{{ number_format($row['trip_cost']) }}</td>
                    <td class="px-4 py-2">{{ number_format($row['delivery_cost']) }}</td>
                    <td class="px-4 py-2 font-semibold {{ $row['profit'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ number_format($row['profit']) }}</td>
                    <td class="px-4 py-2 text-xs">{{ $row['margin_pct'] ?? '—' }}%</td>
                    <td class="px-4 py-2">@if ($row['loss'])<span class="rounded-sm bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">LOSS</span>@endif</td>
                </tr>
            @empty <tr><td colspan="9" class="px-4 py-8 text-center text-sm text-gray-400">No packages.</td></tr> @endforelse
            </tbody></table>
    </div>
</x-app-layout>
