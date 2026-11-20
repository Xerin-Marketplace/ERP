<x-app-layout>
    <x-slot name="header">Account Statement</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-5">
        <form class="flex gap-2 mb-4"><input type="date" name="from" value="{{ request('from') }}" class="rounded border-gray-300 text-sm">
            <input type="date" name="to" value="{{ request('to') }}" class="rounded border-gray-300 text-sm">
            <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Filter</button></form>
        <table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Invoice</th><th>Date</th><th>Total</th><th>Received</th><th>Balance</th><th>Status</th></tr></thead>
        <tbody class="divide-y">@forelse($st['invoices'] as $i)
            <tr><td class="p-2 font-mono">{{ $i['reference'] }}</td><td>{{ $i['date'] }}</td><td>{{ number_format($i['total']) }}</td>
                <td>{{ number_format($i['received']) }}</td><td>{{ number_format($i['balance']) }}</td><td>{{ $i['status'] }}</td></tr>
            @empty<tr><td colspan="6" class="p-4 text-center text-gray-400">No invoices.</td></tr>@endforelse
        </tbody></table>
        <div class="mt-4 pt-3 border-t text-right font-semibold">Closing balance: {{ number_format($st['closing_balance']) }}</div>
    </div>
</x-app-layout>
