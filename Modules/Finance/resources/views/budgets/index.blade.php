<x-app-layout>
    <x-slot name="header">{{ __('Budgets') }}</x-slot>
    <div class="space-y-4">
    @foreach ($budgets as $b)
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold">{{ $b->name }} <span class="text-xs font-mono text-gray-400">{{ $b->reference }} v{{ $b->version }}</span></h3>
                <span class="rounded px-2 py-0.5 text-xs {{ $b->status==='active' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $b->status }} · {{ $b->control_policy }}</span>
            </div>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th>Account</th><th class="text-right">Budget</th><th class="text-right">Consumed</th><th class="text-right">Utilization</th></tr></thead>
                <tbody>
                @foreach ($b->lines as $l)
                    <tr class="border-t">
                        <td class="py-1">{{ $l->account->code }} — {{ $l->account->name }}</td>
                        <td class="py-1 text-right">{{ number_format($l->amount, 2) }}</td>
                        <td class="py-1 text-right">{{ number_format($l->consumed, 2) }}</td>
                        <td class="py-1 text-right {{ $l->consumed > $l->amount ? 'text-red-600 font-bold' : '' }}">{{ $l->amount > 0 ? number_format($l->consumed / $l->amount * 100, 1) : 0 }}%</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
    </div>
</x-app-layout>
