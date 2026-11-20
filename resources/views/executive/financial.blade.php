<x-app-layout>
    <x-slot name="header">Financial Intelligence</x-slot>
    @include('executive._filters')

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @include('executive._kpi', ['title' => 'Revenue', 'kpi' => $kpis['revenue'], 'money' => true, 'color' => 'text-emerald-700'])
        @include('executive._kpi', ['title' => 'Expenses', 'kpi' => $kpis['expenses'], 'money' => true, 'color' => 'text-red-700'])
        @include('executive._kpi', ['title' => 'Net Profit', 'kpi' => $kpis['net_profit'], 'money' => true, 'color' => $kpis['net_profit']['value'] >= 0 ? 'text-emerald-700' : 'text-red-700'])
        @include('executive._kpi', ['title' => 'A/R Outstanding', 'kpi' => $kpis['ar_outstanding'], 'money' => true])
        @include('executive._kpi', ['title' => 'A/P Outstanding', 'kpi' => $kpis['ap_outstanding'], 'money' => true])
        @include('executive._kpi', ['title' => 'Cash & Bank', 'kpi' => ['value' => $metrics['bank_balance'] ?? 0, 'pct' => null], 'money' => true])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Monthly Revenue vs Expenses</h3>
            <div class="relative h-72"><canvas id="finChart"></canvas></div>
        </div>
        <div class="space-y-4">
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Receivables Ageing</h3>
                <ul class="text-sm space-y-1">
                    @foreach (($metrics['ar_aging'] ?? []) as $bucket => $amt)
                    <li class="flex justify-between"><span>{{ str_replace('_', ' ', $bucket) }}</span><b>{{ number_format($amt, 2) }}</b></li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
                <h3 class="text-sm font-semibold mb-2">Payables Ageing</h3>
                <ul class="text-sm space-y-1">
                    @foreach (($metrics['ap_aging'] ?? []) as $bucket => $amt)
                    <li class="flex justify-between"><span>{{ str_replace('_', ' ', $bucket) }}</span><b>{{ number_format($amt, 2) }}</b></li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm text-sm">
                <h3 class="text-sm font-semibold mb-2">Journals</h3>
                <ul class="space-y-1">
                    <li class="flex justify-between"><span>Draft</span><b>{{ $metrics['journals']['draft'] ?? 0 }}</b></li>
                    <li class="flex justify-between"><span>Submitted</span><b class="text-amber-600">{{ $metrics['journals']['submitted'] ?? 0 }}</b></li>
                    <li class="flex justify-between"><span>Posted</span><b class="text-emerald-700">{{ $metrics['journals']['posted'] ?? 0 }}</b></li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('finChart'), {
                type: 'line',
                data: { labels: @js($trend['labels']), datasets: [
                    { label: 'Revenue', data: @js($trend['revenue']), borderColor: '#047857', tension: .35, fill: true, backgroundColor: 'rgba(4,120,87,.08)' },
                    { label: 'Expenses', data: @js($trend['expenses']), borderColor: '#b91c1c', tension: .35, fill: true, backgroundColor: 'rgba(185,28,28,.06)' },
                ]},
                options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom' } } },
            });
        });
    </script>
</x-app-layout>
