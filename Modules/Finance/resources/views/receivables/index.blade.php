<x-app-layout>
    <x-slot name="header">{{ __('Accounts Receivable') }}</x-slot>
    <div class="grid grid-cols-5 gap-3 mb-4">
        @foreach (['current'=>'Current','1_30'=>'1–30d','31_60'=>'31–60d','61_90'=>'61–90d','90_plus'=>'90+d'] as $k=>$l)
            <div class="rounded-sm border border-gray-200 bg-white p-3 shadow-sm"><p class="text-xs text-gray-500">{{ $l }}</p><p class="font-bold">{{ number_format($aging[$k], 2) }}</p></div>
        @endforeach
    </div>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left"><tr><th class="p-3">Ref</th><th class="p-3">Customer</th><th class="p-3">Type</th><th class="p-3">Date</th><th class="p-3">Due</th><th class="p-3 text-right">Total</th><th class="p-3 text-right">Received</th><th class="p-3">Status</th><th class="p-3"></th></tr></thead>
            <tbody>
            @foreach ($invoices as $inv)
                <tr class="border-t">
                    <td class="p-3 font-mono text-xs">{{ $inv->reference }}</td>
                    <td class="p-3">{{ $inv->customer->name }}</td>
                    <td class="p-3">{{ $inv->type }}</td>
                    <td class="p-3">{{ $inv->invoice_date->format('d M Y') }}</td>
                    <td class="p-3 {{ $inv->due_date?->isPast() && !in_array($inv->status,['paid']) ? 'text-red-600' : '' }}">{{ $inv->due_date?->format('d M Y') }}</td>
                    <td class="p-3 text-right">{{ number_format($inv->total, 2) }}</td>
                    <td class="p-3 text-right">{{ number_format($inv->received_amount, 2) }}</td>
                    <td class="p-3"><span class="rounded px-2 py-0.5 text-xs {{ $inv->status==='paid' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $inv->status }}</span></td>
                    <td class="p-3 text-right">@if (in_array($inv->status,['draft','submitted','approved']))
                        <form method="POST" action="{{ route('finance.receivables.post', $inv) }}" class="inline">@csrf<button class="text-xs text-indigo-600">Post to GL</button></form>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $invoices->links() }}</div>
    </div>
</x-app-layout>
