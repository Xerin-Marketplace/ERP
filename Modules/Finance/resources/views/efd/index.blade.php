<x-app-layout>
    <x-slot name="header">EFD / Fiscal Receipts</x-slot>
    <div class="space-y-4">
        @unless ($enabled)
        <div class="rounded-sm border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            EFD transmission is <strong>disabled</strong> — receipts stay queued locally with their TRA payload stored.
            Enable <code class="font-mono text-xs">finance.efd.enabled</code> in Settings only after the tax adviser confirms gateway requirements.
        </div>
        @endunless

        <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left"><tr>
                    <th class="p-3">Reference</th><th class="p-3">Invoice</th><th class="p-3">Customer</th>
                    <th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3">Fiscal #</th><th class="p-3"></th>
                </tr></thead>
                <tbody>
                @forelse ($receipts as $r)
                    <tr class="border-t">
                        <td class="p-3 font-mono text-xs">{{ $r->reference }}</td>
                        <td class="p-3">{{ $r->invoice->reference ?? '—' }}</td>
                        <td class="p-3">{{ $r->invoice->customer->name ?? '—' }}</td>
                        <td class="p-3">{{ $r->invoice->currency ?? '' }} {{ number_format($r->invoice->total ?? 0, 2) }}</td>
                        <td class="p-3"><span class="rounded-sm px-2 py-0.5 text-xs font-medium {{ ['acknowledged','transmitted'] ? '' : '' }}{{ $r->status === 'acknowledged' ? 'bg-emerald-100 text-emerald-700' : ($r->status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700') }}">{{ $r->status }}</span></td>
                        <td class="p-3 font-mono text-xs">{{ $r->receipt_number ?? '—' }}</td>
                        <td class="p-3">
                            @if ($r->status === 'draft')
                            <form method="POST" action="{{ route('finance.efd.queue', $r) }}" class="inline">@csrf
                                <button class="text-xerin-700 text-xs font-semibold">Queue</button></form>
                            @endif
                            @if (in_array($r->status, ['draft','queued','failed']))
                            <form method="POST" action="{{ route('finance.efd.void', $r) }}" class="inline" onsubmit="return confirm('Void this receipt?')">@csrf
                                <input type="hidden" name="reason" value="Manual void">
                                <button class="text-red-600 text-xs font-semibold">Void</button></form>
                            @endif
                        </td>
                    </tr>
                @empty <tr><td colspan="7" class="p-6 text-center text-gray-400">No receipts yet.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
