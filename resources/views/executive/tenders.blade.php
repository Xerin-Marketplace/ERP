<x-app-layout>
    <x-slot name="header">Tender Pipeline</x-slot>
    @include('executive._filters')

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-6">
        @include('executive._kpi', ['title' => 'Pipeline Value (est.)', 'kpi' => ['value' => $tenders['pipeline_value'], 'pct' => null], 'money' => true])
        @include('executive._kpi', ['title' => 'Awarded Value', 'kpi' => ['value' => $tenders['awarded_value'], 'pct' => null], 'money' => true, 'color' => 'text-emerald-700'])
        @include('executive._kpi', ['title' => 'Active Opportunities', 'kpi' => ['value' => $tenders['by_stage']->except(['awarded','lost','cancelled'])->sum(), 'pct' => null]])
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-3">By Stage</h3>
            <div class="relative h-56"><canvas id="tenderChart"></canvas></div>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <h3 class="p-4 pb-0 text-sm font-semibold">Upcoming Deadlines</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr class="border-b"><th class="p-3">Tender</th><th class="p-3">Deadline</th></tr></thead>
                <tbody>
                @forelse ($tenders['upcoming_deadlines'] as $t)
                    <tr class="border-t"><td class="p-3">{{ $t->reference }} — {{ \Illuminate\Support\Str::limit($t->title, 40) }}</td>
                        <td class="p-3 text-xs {{ $t->submission_deadline->diffInDays(now()) < 7 ? 'text-red-600 font-semibold' : '' }}">{{ $t->submission_deadline->toDateString() }}</td></tr>
                @empty <tr><td colspan="2" class="p-6 text-center text-gray-400">No upcoming deadlines.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <h3 class="p-4 pb-0 text-sm font-semibold">Recent Tenders</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr class="border-b"><th class="p-3">Ref</th><th class="p-3">Stage</th></tr></thead>
                <tbody>
                @forelse ($tenders['recent'] as $t)
                    <tr class="border-t"><td class="p-3">{{ $t->reference }} — {{ \Illuminate\Support\Str::limit($t->title, 35) }}</td>
                        <td class="p-3 text-xs">{{ $t->stage }}</td></tr>
                @empty <tr><td colspan="2" class="p-6 text-center text-gray-400">No tenders.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('tenderChart'), {
                type: 'doughnut',
                data: { labels: @js($tenders['by_stage']->keys()->all()), datasets: [
                    { data: @js($tenders['by_stage']->values()->all()), backgroundColor: ['#2563eb','#047857','#d97706','#b91c1c','#6b7280','#7c3aed'] },
                ]},
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } },
            });
        });
    </script>
</x-app-layout>
