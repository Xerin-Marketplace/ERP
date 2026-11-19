<x-app-layout>
    <x-slot name="header">{{ __('Payments') }}</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left"><tr><th class="p-3">Ref</th><th class="p-3">Direction</th><th class="p-3">Method</th><th class="p-3">Date</th><th class="p-3 text-right">Amount</th><th class="p-3">Allocations</th><th class="p-3">Status</th></tr></thead>
            <tbody>
            @foreach ($payments as $p)
                <tr class="border-t">
                    <td class="p-3 font-mono text-xs">{{ $p->reference }}</td>
                    <td class="p-3"><span class="{{ $p->direction==='in' ? 'text-green-600' : 'text-red-600' }}">{{ $p->direction === 'in' ? '↓ in' : '↑ out' }}</span></td>
                    <td class="p-3">{{ $p->method }}</td>
                    <td class="p-3">{{ $p->payment_date->format('d M Y') }}</td>
                    <td class="p-3 text-right font-semibold">{{ number_format($p->amount, 2) }}</td>
                    <td class="p-3 text-xs text-gray-500">{{ $p->allocations->count() }} doc(s)</td>
                    <td class="p-3"><span class="rounded px-2 py-0.5 text-xs bg-gray-100">{{ $p->status }}</span></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $payments->links() }}</div>
    </div>
</x-app-layout>
