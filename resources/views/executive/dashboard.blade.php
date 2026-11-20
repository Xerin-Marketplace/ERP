<x-app-layout>
    <x-slot name="header">Executive Command Center</x-slot>
    @include('executive._filters')

    {{-- KPI grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @include('executive._kpi', ['title' => 'Revenue', 'kpi' => $kpis['revenue'], 'money' => true, 'color' => 'text-emerald-700', 'href' => route('executive.financial', request()->query())])
        @include('executive._kpi', ['title' => 'Expenses', 'kpi' => $kpis['expenses'], 'money' => true, 'color' => 'text-red-700'])
        @include('executive._kpi', ['title' => 'Net Profit', 'kpi' => $kpis['net_profit'], 'money' => true, 'color' => $kpis['net_profit']['value'] >= 0 ? 'text-emerald-700' : 'text-red-700'])
        @include('executive._kpi', ['title' => 'A/R Outstanding', 'kpi' => $kpis['ar_outstanding'], 'money' => true, 'href' => route('executive.financial', request()->query())])
        @include('executive._kpi', ['title' => 'A/P Outstanding', 'kpi' => $kpis['ap_outstanding'], 'money' => true])
        @include('executive._kpi', ['title' => 'Customers', 'kpi' => $kpis['customers'], 'href' => route('executive.sales', request()->query())])
        @include('executive._kpi', ['title' => 'New Customers', 'kpi' => $kpis['new_customers']])
        @include('executive._kpi', ['title' => 'Active Employees', 'kpi' => $kpis['employees'], 'href' => route('executive.employees', request()->query())])
        @include('executive._kpi', ['title' => 'Open Tasks', 'kpi' => $kpis['tasks_open'], 'href' => route('executive.tasks', request()->query())])
        @include('executive._kpi', ['title' => 'Overdue Tasks', 'kpi' => $kpis['tasks_overdue'], 'color' => 'text-red-700', 'href' => route('executive.tasks', request()->query())])
        @include('executive._kpi', ['title' => 'Active Tenders', 'kpi' => $kpis['tenders_active'], 'href' => route('executive.tenders', request()->query())])
        @include('executive._kpi', ['title' => 'Tenders Won', 'kpi' => $kpis['tenders_won'], 'color' => 'text-emerald-700', 'href' => route('executive.tenders', request()->query())])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        {{-- Revenue trend --}}
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Revenue vs Expenses — {{ $from }} → {{ $to }}</h3>
            <div class="relative h-72"><canvas id="trendChart"></canvas></div>
        </div>
        {{-- Activity feed --}}
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Live Activity</h3>
            <ul class="space-y-2 text-xs">
                @forelse ($activity as $a)
                <li class="border-b border-gray-50 pb-2">
                    <span class="font-medium">{{ $a->user->name ?? 'System' }}</span>
                    <span class="text-gray-500">{{ $a->action }}</span>
                    <p class="text-gray-400">{{ $a->created_at->diffForHumans() }}</p>
                </li>
                @empty <li class="text-gray-400">No activity recorded.</li> @endforelse
            </ul>
            <a href="{{ route('executive.activity') }}" class="mt-3 inline-block text-xs font-semibold text-xerin-700">Full audit stream →</a>
        </div>
    </div>

    {{-- Branch comparison --}}
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <h3 class="p-4 pb-0 text-sm font-semibold">Branch Performance</h3>
        <table class="w-full text-sm">
            <thead class="text-left"><tr class="border-b text-xs text-gray-500">
                <th class="p-3">Branch</th><th class="p-3 text-right">Revenue</th><th class="p-3 text-right">Expenses</th>
                <th class="p-3 text-right">Profit</th><th class="p-3 text-right">Staff</th><th class="p-3 text-right">Customers</th><th class="p-3 text-right">Open Tasks</th>
            </tr></thead>
            <tbody>
            @foreach ($branchRows as $row)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-3"><a href="{{ route('executive.branch', $row['branch']) }}" class="font-semibold text-xerin-700">{{ $row['branch']->code }}</a> <span class="text-xs text-gray-400">{{ $row['branch']->name }}</span></td>
                    <td class="p-3 text-right">{{ number_format($row['revenue'], 0) }}</td>
                    <td class="p-3 text-right">{{ number_format($row['expenses'], 0) }}</td>
                    <td class="p-3 text-right font-semibold {{ $row['profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($row['profit'], 0) }}</td>
                    <td class="p-3 text-right">{{ $row['employees'] }}</td>
                    <td class="p-3 text-right">{{ $row['customers'] }}</td>
                    <td class="p-3 text-right">{{ $row['open_tasks'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="p-3 text-xs text-gray-400">Amounts in each branch's posting currency — branches are not summed across currencies.</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: @js($trend['labels']),
                    datasets: [
                        { label: 'Revenue', data: @js($trend['revenue']), borderColor: '#047857', backgroundColor: 'rgba(4,120,87,.08)', tension: .35, fill: true },
                        { label: 'Expenses', data: @js($trend['expenses']), borderColor: '#b91c1c', backgroundColor: 'rgba(185,28,28,.06)', tension: .35, fill: true },
                    ],
                },
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { ticks: { callback: v => v.toLocaleString() } } } },
            });
        });
    </script>
</x-app-layout>
