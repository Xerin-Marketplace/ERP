<x-app-layout>
    <x-slot name="header">Sales Intelligence</x-slot>
    @include('executive._filters')

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
        @include('executive._kpi', ['title' => 'Gross Sales', 'kpi' => $sales['gross_sales'], 'money' => true, 'color' => 'text-emerald-700'])
        @include('executive._kpi', ['title' => 'Invoices Issued', 'kpi' => $sales['invoices']])
        @include('executive._kpi', ['title' => 'Avg Invoice Value', 'kpi' => $sales['avg_invoice'], 'money' => true])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">Sales by Month</h3>
            <div class="relative h-64"><canvas id="salesChart"></canvas></div>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <h3 class="p-4 pb-0 text-sm font-semibold">Top Customers</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr class="border-b"><th class="p-3">Customer</th><th class="p-3 text-right">Invoices</th><th class="p-3 text-right">Total</th></tr></thead>
                <tbody>
                @forelse ($sales['by_customer'] as $row)
                    <tr class="border-t"><td class="p-3">{{ $row['customer']->name ?? '—' }}</td>
                        <td class="p-3 text-right">{{ $row['n'] }}</td>
                        <td class="p-3 text-right font-semibold">{{ number_format($row['amt'], 2) }}</td></tr>
                @empty <tr><td colspan="3" class="p-6 text-center text-gray-400">No posted invoices in period.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('salesChart'), {
                type: 'bar',
                data: { labels: @js($sales['by_month']->keys()->all()), datasets: [
                    { label: 'Invoiced Sales', data: @js($sales['by_month']->values()->all()), backgroundColor: '#047857' },
                ]},
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
            });
        });
    </script>
</x-app-layout>
