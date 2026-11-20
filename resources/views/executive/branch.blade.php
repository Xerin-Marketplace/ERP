<x-app-layout>
    <x-slot name="header">{{ $branch->name }} — Branch Intelligence</x-slot>
    @include('executive._filters')

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        @include('executive._kpi', ['title' => 'Revenue', 'kpi' => $kpis['revenue'], 'money' => true, 'color' => 'text-emerald-700'])
        @include('executive._kpi', ['title' => 'Expenses', 'kpi' => $kpis['expenses'], 'money' => true, 'color' => 'text-red-700'])
        @include('executive._kpi', ['title' => 'Net Profit', 'kpi' => $kpis['net_profit'], 'money' => true])
        @include('executive._kpi', ['title' => 'Customers', 'kpi' => $kpis['customers']])
        @include('executive._kpi', ['title' => 'Employees', 'kpi' => $kpis['employees']])
        @include('executive._kpi', ['title' => 'Open Tasks', 'kpi' => $kpis['tasks_open']])
    </div>

    <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm mb-4">
        <h3 class="text-sm font-semibold mb-3">Revenue vs Expenses ({{ $branch->currency ?? '' }})</h3>
        <div class="relative h-64"><canvas id="bChart"></canvas></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <h3 class="p-4 pb-0 text-sm font-semibold">Departments</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr class="border-b"><th class="p-3">Department</th><th class="p-3 text-right">Staff</th><th class="p-3 text-right">Open</th><th class="p-3 text-right">Overdue</th><th class="p-3 text-right">Done</th></tr></thead>
                <tbody>
                @foreach ($departments as $d)
                    <tr class="border-t"><td class="p-3">{{ $d['department']->name }}</td>
                        <td class="p-3 text-right">{{ $d['staff'] }}</td><td class="p-3 text-right">{{ $d['tasks_open'] }}</td>
                        <td class="p-3 text-right {{ $d['tasks_overdue'] ? 'text-red-600 font-semibold' : '' }}">{{ $d['tasks_overdue'] }}</td>
                        <td class="p-3 text-right">{{ $d['tasks_done'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <h3 class="p-4 pb-0 text-sm font-semibold">Staff</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr class="border-b"><th class="p-3">Employee</th><th class="p-3">Position</th><th class="p-3 text-right">Open</th><th class="p-3 text-right">Done</th></tr></thead>
                <tbody>
                @foreach ($employees as $row)
                    <tr class="border-t"><td class="p-3">{{ $row['employee']->user->name ?? $row['employee']->employee_number }}</td>
                        <td class="p-3 text-xs text-gray-500">{{ $row['employee']->position->title ?? '—' }}</td>
                        <td class="p-3 text-right">{{ $row['tasks_open'] }}</td><td class="p-3 text-right">{{ $row['tasks_done'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('bChart'), {
                type: 'line',
                data: { labels: @js($trend['labels']), datasets: [
                    { label: 'Revenue', data: @js($trend['revenue']), borderColor: '#047857', tension: .35, fill: true, backgroundColor: 'rgba(4,120,87,.08)' },
                    { label: 'Expenses', data: @js($trend['expenses']), borderColor: '#b91c1c', tension: .35, fill: true, backgroundColor: 'rgba(185,28,28,.06)' },
                ]},
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
            });
        });
    </script>
</x-app-layout>
