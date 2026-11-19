<x-app-layout>
    <x-slot name="header">{{ __('Ticket Reports') }}</x-slot>
    <form method="GET" class="mb-4 flex gap-3 items-end">
        <input type="date" name="from" value="{{ request('from') }}" class="rounded-sm border-gray-300 text-sm">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded-sm border-gray-300 text-sm">
        <button class="rounded-sm bg-xerin-600 px-4 py-2 text-sm font-semibold text-white">Apply</button>
    </form>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        @php $cards = [
            ['Created', $report['created']], ['Resolved', $report['resolved']], ['Closed', $report['closed']],
            ['Reopened', $report['reopened']], ['SLA Breaches', $report['sla_breaches']],
            ['SLA Compliance', $report['sla_compliance_pct'].'%'],
            ['Avg 1st Response', $report['avg_first_response_minutes'] ? round($report['avg_first_response_minutes']).'m' : '—'],
            ['Avg Resolution', $report['avg_resolution_minutes'] ? round($report['avg_resolution_minutes']/60, 1).'h' : '—'],
        ]; @endphp
        @foreach ($cards as [$l, $v])
            <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">{{ $l }}</p><p class="mt-1 text-xl font-bold">{{ $v }}</p></div>
        @endforeach
    </div>
    <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="text-sm font-semibold mb-3">Agent Workload (open)</h3>
        <ul class="text-sm space-y-1">
            @forelse ($report['agent_workload'] as $a)
                <li class="flex justify-between"><span>{{ $a['agent'] }}</span><span class="font-semibold">{{ $a['open'] }}</span></li>
            @empty <li class="text-gray-400">No assignments.</li> @endforelse
        </ul>
    </div>
</x-app-layout>
