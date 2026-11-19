<x-app-layout>
    <x-slot name="header">{{ __('Finance Dashboard') }}</x-slot>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Total Revenue</p><p class="mt-1 text-2xl font-bold text-green-700">{{ number_format($metrics['total_revenue'], 2) }}</p></div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Total Expenses</p><p class="mt-1 text-2xl font-bold text-red-700">{{ number_format($metrics['total_expenses'], 2) }}</p></div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Net Profit / Loss</p><p class="mt-1 text-2xl font-bold {{ $metrics['net_profit'] >= 0 ? 'text-green-700' : 'text-red-700' }}">{{ number_format($metrics['net_profit'], 2) }}</p></div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm"><p class="text-xs text-gray-500">Cash &amp; Bank</p><p class="mt-1 text-2xl font-bold">{{ number_format($metrics['bank_balance'], 2) }}</p></div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Receivables</h3>
            <ul class="text-sm space-y-1">
                <li class="flex justify-between"><span>Outstanding</span><b>{{ number_format($metrics['ar_outstanding'], 2) }}</b></li>
                <li class="flex justify-between"><span>Overdue invoices</span><b class="text-red-600">{{ $metrics['overdue_receivables'] }}</b></li>
                <li class="flex justify-between"><span>90+ days</span><b>{{ number_format($metrics['ar_aging']['90_plus'], 2) }}</b></li>
            </ul>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Payables</h3>
            <ul class="text-sm space-y-1">
                <li class="flex justify-between"><span>Outstanding</span><b>{{ number_format($metrics['ap_outstanding'], 2) }}</b></li>
                <li class="flex justify-between"><span>Overdue invoices</span><b class="text-red-600">{{ $metrics['overdue_payables'] }}</b></li>
                <li class="flex justify-between"><span>90+ days</span><b>{{ number_format($metrics['ap_aging']['90_plus'], 2) }}</b></li>
            </ul>
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="text-sm font-semibold mb-2">Journals</h3>
            <ul class="text-sm space-y-1">
                <li class="flex justify-between"><span>Draft</span><b>{{ $metrics['journals']['draft'] }}</b></li>
                <li class="flex justify-between"><span>Awaiting approval</span><b class="text-amber-600">{{ $metrics['journals']['submitted'] }}</b></li>
                <li class="flex justify-between"><span>Posted</span><b class="text-green-600">{{ $metrics['journals']['posted'] }}</b></li>
                <li class="flex justify-between"><span>Pending expenses</span><b>{{ $metrics['pending_expenses'] }}</b></li>
            </ul>
        </div>
    </div>
</x-app-layout>
