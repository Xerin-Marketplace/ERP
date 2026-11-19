<x-app-layout>
    <x-slot name="header">{{ __('Financial Reports') }}</x-slot>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold mb-2">Income Statement</h3>
            <div class="text-sm space-y-1">
                <p class="text-xs text-gray-500 uppercase mt-2">Revenue</p>
                @foreach ($is['revenue'] as $r)<div class="flex justify-between"><span>{{ $r['account']->name }}</span><b>{{ number_format($r['balance'], 2) }}</b></div>@endforeach
                <p class="text-xs text-gray-500 uppercase mt-2">Expenses</p>
                @foreach ($is['expenses'] as $r)<div class="flex justify-between"><span>{{ $r['account']->name }}</span><b>{{ number_format($r['balance'], 2) }}</b></div>@endforeach
                <div class="flex justify-between border-t pt-2 font-bold"><span>Net</span><span class="{{ $is['revenue']->sum('balance') - $is['expenses']->sum('balance') >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ number_format($is['revenue']->sum('balance') - $is['expenses']->sum('balance'), 2) }}</span></div>
            </div>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold mb-2">Balance Sheet</h3>
            <div class="text-sm space-y-1">
                <p class="text-xs text-gray-500 uppercase">Assets</p>
                @foreach ($bs['assets'] as $r)<div class="flex justify-between"><span>{{ $r['account']->name }}</span><b>{{ number_format($r['balance'], 2) }}</b></div>@endforeach
                <p class="text-xs text-gray-500 uppercase mt-2">Liabilities</p>
                @foreach ($bs['liabilities'] as $r)<div class="flex justify-between"><span>{{ $r['account']->name }}</span><b>{{ number_format($r['balance'], 2) }}</b></div>@endforeach
                <p class="text-xs text-gray-500 uppercase mt-2">Equity</p>
                @foreach ($bs['equity'] as $r)<div class="flex justify-between"><span>{{ $r['account']->name }}</span><b>{{ number_format($r['balance'], 2) }}</b></div>@endforeach
            </div>
        </div>
    </div>
    <div class="mt-4 rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
        <h3 class="font-semibold mb-2">Trial Balance <a href="{{ route('finance.reports.trial-balance') }}" class="text-xs text-indigo-600 float-right">Full view →</a></h3>
        <table class="w-full text-sm">
            <thead class="text-left text-xs text-gray-500"><tr><th>Account</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead>
            <tbody>
            @foreach ($tb['rows']->take(10) as $row)
                <tr class="border-t"><td class="py-1">{{ $row['account']->code }} — {{ $row['account']->name }}</td><td class="py-1 text-right">{{ number_format($row['debit'], 2) }}</td><td class="py-1 text-right">{{ number_format($row['credit'], 2) }}</td></tr>
            @endforeach
            </tbody>
            <tfoot class="border-t font-bold"><tr><td class="py-1">Totals</td><td class="py-1 text-right">{{ number_format($tb['total_debit'], 2) }}</td><td class="py-1 text-right">{{ number_format($tb['total_credit'], 2) }}</td></tr></tfoot>
        </table>
    </div>
</x-app-layout>
