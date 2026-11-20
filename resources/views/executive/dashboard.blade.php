<x-app-layout>
    <x-slot name="header">Executive Command Center</x-slot>

    {{-- Hero strip --}}
    <div class="mb-5 overflow-hidden rounded-xl bg-gradient-to-r from-slate-900 via-xerin-900 to-slate-900 p-5 text-white shadow-md">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold tracking-tight">Xerin Group — Command Center</h2>
                <p class="mt-0.5 text-xs text-slate-300">
                    {{ $from }} → {{ $to }}
                    · {{ $branchId ? $branches->firstWhere('id', $branchId)?->name : 'All branches' }}
                    · Data refreshed {{ $refreshedAt->format('H:i') }}
                </p>
            </div>
            <div class="text-right">
                <p id="cc-clock" class="text-2xl font-bold tabular-nums tracking-tight">{{ now()->format('H:i:s') }}</p>
                <p class="text-[11px] uppercase tracking-widest text-slate-400">{{ now()->format('l, d M Y') }}</p>
            </div>
        </div>
    </div>

    @include('executive._filters')

    {{-- ── FINANCIAL PULSE ──────────────────────────── --}}
    <p class="mb-2 text-[11px] font-bold uppercase tracking-widest text-gray-400">Financial pulse</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-6">
        @include('executive._kpi', ['title' => 'Revenue', 'kpi' => $kpis['revenue'], 'money' => true, 'bg' => 'from-emerald-500 to-emerald-700', 'spark' => 'revenue', 'href' => route('executive.financial', request()->query())])
        @include('executive._kpi', ['title' => 'Expenses', 'kpi' => $kpis['expenses'], 'money' => true, 'bg' => 'from-rose-500 to-red-700', 'spark' => 'expenses', 'invert' => true])
        @include('executive._kpi', ['title' => 'Net Profit', 'kpi' => $kpis['net_profit'], 'money' => true, 'bg' => 'from-sky-500 to-indigo-700', 'spark' => 'profit', 'href' => route('executive.financial', request()->query())])
        @include('executive._kpi', ['title' => 'Profit Margin', 'kpi' => $kpis['margin'], 'percent' => true, 'bg' => 'from-violet-500 to-purple-700'])
        @include('executive._kpi', ['title' => 'Cash Collected', 'kpi' => $kpis['cash_collected'], 'money' => true, 'bg' => 'from-teal-500 to-cyan-700'])
        @include('executive._kpi', ['title' => 'A/R Outstanding', 'kpi' => $kpis['ar_outstanding'], 'money' => true, 'bg' => 'from-amber-500 to-orange-700', 'invert' => true, 'href' => route('executive.financial', request()->query())])
        @include('executive._kpi', ['title' => 'A/R Overdue', 'kpi' => $kpis['ar_overdue'], 'money' => true, 'bg' => 'from-red-600 to-rose-800', 'invert' => true])
        @include('executive._kpi', ['title' => 'A/P Outstanding', 'kpi' => $kpis['ap_outstanding'], 'money' => true, 'bg' => 'from-orange-500 to-amber-700', 'invert' => true])
    </div>

    {{-- ── OPERATIONS PULSE ─────────────────────────── --}}
    <p class="mb-2 text-[11px] font-bold uppercase tracking-widest text-gray-400">Operations pulse</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @include('executive._kpi', ['title' => 'Pkgs Received', 'kpi' => $ops['packages_received'], 'bg' => 'from-sky-500 to-blue-700', 'spark' => 'packages'])
        @include('executive._kpi', ['title' => 'In Warehouse', 'kpi' => $ops['in_warehouse'], 'bg' => 'from-cyan-500 to-teal-700'])
        @include('executive._kpi', ['title' => 'Active Bookings', 'kpi' => $ops['active_bookings'], 'bg' => 'from-indigo-500 to-violet-700'])
        @include('executive._kpi', ['title' => 'Pending Releases', 'kpi' => $ops['pending_releases'], 'bg' => 'from-amber-500 to-yellow-700', 'invert' => true])
        @include('executive._kpi', ['title' => 'Delivered', 'kpi' => $ops['deliveries_completed'], 'bg' => 'from-emerald-500 to-green-700'])
        @include('executive._kpi', ['title' => 'Active Deliveries', 'kpi' => $ops['deliveries_active'], 'bg' => 'from-lime-500 to-emerald-700'])
    </div>

    {{-- ── BUSINESS PULSE ───────────────────────────── --}}
    <p class="mb-2 text-[11px] font-bold uppercase tracking-widest text-gray-400">Business pulse</p>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @include('executive._kpi', ['title' => 'Customers', 'kpi' => $kpis['customers'], 'bg' => 'from-blue-500 to-indigo-700', 'href' => route('executive.sales', request()->query())])
        @include('executive._kpi', ['title' => 'New Customers', 'kpi' => $kpis['new_customers'], 'bg' => 'from-sky-500 to-cyan-700'])
        @include('executive._kpi', ['title' => 'Employees', 'kpi' => $kpis['employees'], 'bg' => 'from-fuchsia-500 to-pink-700', 'href' => route('executive.employees', request()->query())])
        @include('executive._kpi', ['title' => 'Open Tasks', 'kpi' => $kpis['tasks_open'], 'bg' => 'from-slate-500 to-gray-700', 'href' => route('executive.tasks', request()->query())])
        @include('executive._kpi', ['title' => 'Overdue Tasks', 'kpi' => $kpis['tasks_overdue'], 'bg' => 'from-red-500 to-rose-700', 'invert' => true, 'href' => route('executive.tasks', request()->query())])
        @include('executive._kpi', ['title' => 'Tenders Won', 'kpi' => $kpis['tenders_won'], 'bg' => 'from-emerald-500 to-teal-700', 'href' => route('executive.tenders', request()->query())])
    </div>

    {{-- ── CHARTS ROW 1: trend + branch mix ─────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Revenue vs Expenses — {{ $from }} → {{ $to }}</h3>
            <div class="relative h-72"><canvas id="trendChart"></canvas></div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-1">Revenue by Branch</h3>
            <p class="text-[11px] text-gray-400 mb-2">Each branch in its own posting currency</p>
            <div class="relative h-64"><canvas id="branchDonut"></canvas></div>
        </div>
    </div>

    {{-- ── CHARTS ROW 2: pipeline + tasks + activity ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Cargo → Delivery Pipeline</h3>
            <div class="relative h-56"><canvas id="funnelChart"></canvas></div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Tasks by Status</h3>
            <div class="relative h-56"><canvas id="taskDonut"></canvas></div>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Live Activity</h3>
            <ul class="space-y-2 text-xs max-h-56 overflow-y-auto pr-1">
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
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-x-auto">
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
        window.SPARK = @js($sparklines);
        document.addEventListener('DOMContentLoaded', () => {
            // Live clock
            setInterval(() => {
                const el = document.getElementById('cc-clock');
                if (el) el.textContent = new Date().toLocaleTimeString('en-GB');
            }, 1000);

            // KPI sparklines — real daily series
            document.querySelectorAll('canvas.kpi-spark').forEach(c => {
                const key = c.dataset.key, data = window.SPARK[key] || [];
                new Chart(c, {
                    type: 'line',
                    data: { labels: window.SPARK.labels, datasets: [{
                        data, borderColor: c.dataset.color, borderWidth: 1.5,
                        pointRadius: 0, tension: .4, fill: true,
                        backgroundColor: c.dataset.color + '1A',
                    }]},
                    options: { responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { display: false }, tooltip: { enabled: true } },
                        scales: { x: { display: false }, y: { display: false } } },
                });
            });

            const PALETTE = ['#0369a1','#059669','#d97706','#dc2626','#7c3aed','#0891b2','#be185d','#65a30d'];

            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: @js($trend['labels']),
                    datasets: [
                        { label: 'Revenue', data: @js($trend['revenue']), borderColor: '#047857', backgroundColor: 'rgba(4,120,87,.10)', tension: .35, fill: true },
                        { label: 'Expenses', data: @js($trend['expenses']), borderColor: '#b91c1c', backgroundColor: 'rgba(185,28,28,.07)', tension: .35, fill: true },
                    ],
                },
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom' } },
                    scales: { y: { ticks: { callback: v => v.toLocaleString() } } } },
            });

            const br = @js($branchRows->map(fn ($r) => ['code' => $r['branch']->code, 'revenue' => $r['revenue']]));
            new Chart(document.getElementById('branchDonut'), {
                type: 'doughnut',
                data: { labels: br.map(b => b.code),
                    datasets: [{ data: br.map(b => b.revenue), backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                    plugins: { legend: { position: 'bottom' },
                        tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + c.parsed.toLocaleString() } } } },
            });

            const funnel = @js($ops['funnel']);
            new Chart(document.getElementById('funnelChart'), {
                type: 'bar',
                data: { labels: funnel.labels,
                    datasets: [{ data: funnel.values, backgroundColor: PALETTE.slice(0, 5), borderRadius: 4 }] },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { x: { ticks: { precision: 0 }, grid: { color: '#f1f5f9' } }, y: { grid: { display: false } } } },
            });

            const ts = @js($taskStats['by_status']);
            new Chart(document.getElementById('taskDonut'), {
                type: 'doughnut',
                data: { labels: Object.keys(ts).map(s => s.replace(/_/g, ' ')),
                    datasets: [{ data: Object.values(ts), backgroundColor: PALETTE, borderWidth: 2, borderColor: '#fff' }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '62%',
                    plugins: { legend: { position: 'bottom' } } },
            });
        });
    </script>
</x-app-layout>
