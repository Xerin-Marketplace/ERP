<x-app-layout>
    <x-slot name="header">Branch Performance</x-slot>
    @include('executive._filters')
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-left"><tr class="border-b text-xs text-gray-500">
                <th class="p-3">Branch</th><th class="p-3">Currency</th>
                <th class="p-3 text-right">Revenue</th><th class="p-3 text-right">Expenses</th><th class="p-3 text-right">Profit</th>
                <th class="p-3 text-right">Staff</th><th class="p-3 text-right">Customers</th><th class="p-3 text-right">Open Tasks</th>
            </tr></thead>
            <tbody>
            @foreach ($rows->sortByDesc('profit') as $row)
                <tr class="border-t hover:bg-gray-50">
                    <td class="p-3"><a href="{{ route('executive.branch', $row['branch']) }}" class="font-semibold text-xerin-700">{{ $row['branch']->name }}</a></td>
                    <td class="p-3 text-xs">{{ $row['branch']->currency ?? '—' }}</td>
                    <td class="p-3 text-right">{{ number_format($row['revenue'], 2) }}</td>
                    <td class="p-3 text-right">{{ number_format($row['expenses'], 2) }}</td>
                    <td class="p-3 text-right font-semibold {{ $row['profit'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format($row['profit'], 2) }}</td>
                    <td class="p-3 text-right">{{ $row['employees'] }}</td>
                    <td class="p-3 text-right">{{ $row['customers'] }}</td>
                    <td class="p-3 text-right">{{ $row['open_tasks'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="p-3 text-xs text-gray-400">Each row is in that branch's currency — no cross-currency summation.</p>
    </div>
    <div class="mt-4 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold mb-3">Profit by Branch</h3>
        <div class="relative h-56"><canvas id="branchChart"></canvas></div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('branchChart'), {
                type: 'bar',
                data: { labels: @js($rows->pluck('branch.code')->all()), datasets: [
                    { label: 'Profit', data: @js($rows->pluck('profit')->all()), backgroundColor: @js($rows->map(fn ($r) => $r['profit'] >= 0 ? '#047857' : '#b91c1c')->all()) },
                ]},
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } },
            });
        });
    </script>
</x-app-layout>
