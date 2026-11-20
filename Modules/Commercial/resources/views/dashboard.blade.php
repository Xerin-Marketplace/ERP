<x-app-layout>
    <x-slot name="header">Commercial Dashboard</x-slot>
    <div class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach ([['Customers', $stats['customers'], 'xerin'], ['Active Customers', $stats['active_customers'], 'emerald'], ['Open Leads', $stats['leads'], 'amber'], ['Open Inquiries', $stats['open_inquiries'], 'blue'], ['Quotes Sent', $stats['quotes_sent'], 'indigo'], ['Accepted', $stats['quotes_accepted'], 'green'], ['Quoted Value', number_format($stats['quoted_value'], 0), 'violet'], ['Accepted Value', number_format($stats['accepted_value'], 0), 'teal']] as [$label, $val, $color])
                <div class="bg-white rounded-md border border-gray-200 p-4">
                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $label }}</div>
                    <div class="mt-1 text-2xl font-semibold text-gray-900">{{ $val }}</div>
                </div>
            @endforeach
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-md border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Pipeline by Stage</h3>
                <div class="space-y-2">
                    @foreach ($stats['leads_by_stage'] as $stage => $n)
                        <div class="flex items-center gap-3">
                            <span class="w-32 text-xs text-gray-600 capitalize">{{ str_replace('_',' ',$stage) }}</span>
                            <div class="flex-1 h-2 bg-gray-100 rounded-full"><div class="h-2 bg-xerin-500 rounded-full" style="width: {{ min(100, $n * 10) }}%"></div></div>
                            <span class="text-xs font-medium text-gray-700 w-8 text-right">{{ $n }}</span>
                        </div>
                    @endforeach
                    @if (empty($stats['leads_by_stage'])) <p class="text-sm text-gray-400">No leads yet.</p> @endif
                </div>
            </div>
            <div class="bg-white rounded-md border border-gray-200 p-5">
                <h3 class="font-semibold text-gray-900 mb-3">Lead Sources</h3>
                <div class="space-y-2">
                    @foreach ($stats['leads_by_source'] as $src => $n)
                        <div class="flex items-center gap-3">
                            <span class="w-32 text-xs text-gray-600 capitalize">{{ $src ?: 'unknown' }}</span>
                            <div class="flex-1 h-2 bg-gray-100 rounded-full"><div class="h-2 bg-indigo-500 rounded-full" style="width: {{ min(100, $n * 10) }}%"></div></div>
                            <span class="text-xs font-medium text-gray-700 w-8 text-right">{{ $n }}</span>
                        </div>
                    @endforeach
                    @if (empty($stats['leads_by_source'])) <p class="text-sm text-gray-400">No data yet.</p> @endif
                </div>
            </div>
        </div>
        <div class="bg-white rounded-md border border-gray-200">
            <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-900">Recent Quotations</div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2 text-left">Number</th><th class="px-3 py-2 text-left">Customer</th><th class="px-3 py-2 text-left">Status</th><th class="px-5 py-2 text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recent_quotes as $q)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-2.5 font-mono text-xs"><a class="text-xerin-700 hover:underline" href="{{ route('commercial.quotations.show', $q) }}">{{ $q->number }}</a></td>
                            <td class="px-3 py-2.5">{{ $q->customer?->name ?? '—' }}</td>
                            <td class="px-3 py-2.5"><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ $q->status }}</span></td>
                            <td class="px-5 py-2.5 text-right font-medium">{{ number_format($q->total, 2) }} {{ $q->currency }}</td>
                        </tr>
                    @empty <tr><td colspan="4" class="px-5 py-6 text-center text-gray-400">No quotations yet.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
