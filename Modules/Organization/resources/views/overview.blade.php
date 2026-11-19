<x-app-layout>
    <x-slot name="header">{{ __('Organization Overview') }}</x-slot>

    <!-- Stat cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        @php
            $cards = [
                ['label' => 'Companies', 'value' => $stats['companies_total'], 'sub' => $stats['companies_active'].' active', 'icon' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21', 'href' => 'organization.companies.index'],
                ['label' => 'Subsidiaries', 'value' => $stats['subsidiaries'], 'sub' => 'linked companies', 'icon' => 'M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5'],
                ['label' => 'Branches', 'value' => $stats['branches_total'], 'sub' => $stats['branches_active'].' active · '.$stats['branches_suspended'].' suspended', 'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z', 'href' => 'organization.branches.index'],
                ['label' => 'Departments', 'value' => $stats['departments_total'], 'sub' => $stats['departments_active'].' active', 'icon' => 'M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z', 'href' => 'organization.departments.index'],
            ];
        @endphp
        @foreach ($cards as $card)
            <a @isset($card['href']) href="{{ route($card['href']) }}" @endisset
               class="block rounded-sm border border-gray-200 bg-white p-5 hover:border-gray-300 transition-colors">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500">{{ $card['label'] }}</p>
                    <svg class="w-5 h-5 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}"/></svg>
                </div>
                <p class="mt-3 text-3xl font-bold text-gray-900">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-gray-500">{{ $card['sub'] }}</p>
            </a>
        @endforeach
    </div>

    <!-- Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="rounded-sm border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Branches by status</h3>
            <div class="h-48"><canvas id="branchStatusChart"></canvas></div>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Branches by region</h3>
            <div class="h-48"><canvas id="regionChart"></canvas></div>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Departments by status</h3>
            <div class="h-48"><canvas id="deptChart"></canvas></div>
        </div>
    </div>

    <!-- Recent activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white">
            <div class="px-5 py-4 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-900">Recent branches</h3></div>
            <ul class="divide-y divide-gray-100">
                @forelse ($stats['recent_branches'] as $b)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <a href="{{ route('organization.branches.show', $b) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $b->name }}</a>
                            <p class="text-xs text-gray-500">{{ $b->company?->legal_name }} · {{ $b->city ?? '—' }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @include('organization::partials.status-badge', ['status' => $b->status])
                            <span class="text-xs text-gray-400">{{ $b->created_at->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No branches yet.</li>
                @endforelse
            </ul>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white">
            <div class="px-5 py-4 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-900">Recent companies</h3></div>
            <ul class="divide-y divide-gray-100">
                @forelse ($stats['recent_companies'] as $c)
                    <li class="flex items-center justify-between px-5 py-3">
                        <div>
                            <a href="{{ route('organization.companies.show', $c) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">{{ $c->trading_name ?: $c->legal_name }}</a>
                            <p class="text-xs text-gray-500">{{ $c->code }} · {{ ucfirst($c->company_type) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            @include('organization::partials.status-badge', ['status' => $c->status])
                            <span class="text-xs text-gray-400">{{ $c->created_at->diffForHumans() }}</span>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-gray-400">No companies yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const colors = { active: '#16a34a', suspended: '#d97706', archived: '#9ca3af' };
            const bs = @json($stats['branches_by_status']);
            new Chart(document.getElementById('branchStatusChart'), {
                type: 'doughnut',
                data: { labels: Object.keys(bs).map(s => s[0].toUpperCase()+s.slice(1)), datasets: [{ data: Object.values(bs), backgroundColor: Object.keys(bs).map(s => colors[s] || '#e5e7eb'), borderWidth: 0 }] },
                options: { cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }, maintainAspectRatio: false }
            });

            const rg = @json($stats['branches_by_region']);
            new Chart(document.getElementById('regionChart'), {
                type: 'bar',
                data: { labels: Object.keys(rg), datasets: [{ data: Object.values(rg), backgroundColor: '#6366f1', borderRadius: 3, maxBarThickness: 24 }] },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } }, x: { grid: { display: false }, ticks: { font: { size: 10 } } } }, maintainAspectRatio: false }
            });

            const ds = @json($stats['departments_by_status']);
            new Chart(document.getElementById('deptChart'), {
                type: 'doughnut',
                data: { labels: Object.keys(ds).map(s => s[0].toUpperCase()+s.slice(1)), datasets: [{ data: Object.values(ds), backgroundColor: Object.keys(ds).map(s => colors[s] || '#e5e7eb'), borderWidth: 0 }] },
                options: { cutout: '65%', plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } }, maintainAspectRatio: false }
            });
        });
    </script>
</x-app-layout>
