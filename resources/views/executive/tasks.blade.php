<x-app-layout>
    <x-slot name="header">Tasks &amp; Workload</x-slot>
    @include('executive._filters')

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        @include('executive._kpi', ['title' => 'Overdue', 'kpi' => ['value' => $tasks['overdue'], 'pct' => null], 'color' => 'text-red-700'])
        @include('executive._kpi', ['title' => 'Completed This Month', 'kpi' => ['value' => $tasks['completed_this_month'], 'pct' => null], 'color' => 'text-emerald-700'])
        @include('executive._kpi', ['title' => 'Total Tracked', 'kpi' => ['value' => $tasks['by_status']->sum(), 'pct' => null]])
        @include('executive._kpi', ['title' => 'Statuses', 'kpi' => ['value' => $tasks['by_status']->count(), 'pct' => null]])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">By Status</h3>
            <div class="relative h-56"><canvas id="taskChart"></canvas></div>
        </div>
        <div class="lg:col-span-2 rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <h3 class="p-4 pb-0 text-sm font-semibold">Recent Tasks</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr class="border-b">
                    <th class="p-3">Ref</th><th class="p-3">Title</th><th class="p-3">Assignee</th><th class="p-3">Dept</th><th class="p-3">Status</th><th class="p-3">Due</th>
                </tr></thead>
                <tbody>
                @forelse ($tasks['recent'] as $t)
                    <tr class="border-t">
                        <td class="p-3 font-mono text-xs">{{ $t->ref }}</td>
                        <td class="p-3">{{ $t->title }}</td>
                        <td class="p-3 text-xs">{{ $t->assignee->name ?? '—' }}</td>
                        <td class="p-3 text-xs">{{ $t->department->name ?? '—' }}</td>
                        <td class="p-3"><span class="rounded-sm px-2 py-0.5 text-xs {{ $t->status === 'completed' ? 'bg-emerald-100 text-emerald-700' : ($t->due_at && $t->due_at->isPast() && $t->status !== 'completed' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">{{ $t->status }}</span></td>
                        <td class="p-3 text-xs">{{ $t->due_at?->toDateString() ?? '—' }}</td>
                    </tr>
                @empty <tr><td colspan="6" class="p-6 text-center text-gray-400">No tasks yet.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('taskChart'), {
                type: 'doughnut',
                data: { labels: @js($tasks['by_status']->keys()->all()), datasets: [
                    { data: @js($tasks['by_status']->values()->all()), backgroundColor: ['#047857','#2563eb','#d97706','#b91c1c','#6b7280','#7c3aed'] },
                ]},
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
            });
        });
    </script>
</x-app-layout>
