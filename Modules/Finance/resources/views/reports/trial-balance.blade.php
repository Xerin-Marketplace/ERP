<x-app-layout>
    <x-slot name="header">{{ __('Trial Balance') }}</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left"><tr><th class="p-3">Code</th><th class="p-3">Account</th><th class="p-3">Type</th><th class="p-3 text-right">Debit</th><th class="p-3 text-right">Credit</th><th class="p-3 text-right">Balance</th></tr></thead>
            <tbody>
            @foreach ($tb['rows'] as $row)
                <tr class="border-t"><td class="p-3 font-mono">{{ $row['account']->code }}</td><td class="p-3">{{ $row['account']->name }}</td><td class="p-3">{{ $row['account']->type }}</td><td class="p-3 text-right">{{ number_format($row['debit'], 2) }}</td><td class="p-3 text-right">{{ number_format($row['credit'], 2) }}</td><td class="p-3 text-right font-semibold">{{ number_format($row['balance'], 2) }}</td></tr>
            @endforeach
            </tbody>
            <tfoot class="bg-gray-50 font-bold"><tr><td class="p-3" colspan="3">Totals {{ abs($tb['total_debit'] - $tb['total_credit']) < 0.01 ? '(balanced ✓)' : '(UNBALANCED ✗)' }}</td><td class="p-3 text-right">{{ number_format($tb['total_debit'], 2) }}</td><td class="p-3 text-right">{{ number_format($tb['total_credit'], 2) }}</td><td></td></tr></tfoot>
        </table>
    </div>
</x-app-layout>
