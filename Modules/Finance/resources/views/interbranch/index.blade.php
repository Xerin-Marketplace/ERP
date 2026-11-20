<x-app-layout>
    <x-slot name="header">{{ __('Inter-Branch Ledger') }}</x-slot>

    <form method="GET" class="mb-4 flex items-end gap-2">
        <div><label class="text-xs text-gray-500">Month</label>
            <input type="month" name="month" value="{{ $month }}" class="block rounded-sm border-gray-300 text-sm"></div>
        <button class="rounded-sm bg-xerin-700 px-4 py-1.5 text-sm font-semibold text-white">Reconcile</button>
    </form>

    @if ($pairs->isNotEmpty())
    <div class="mb-4 grid gap-3 md:grid-cols-3">
        @foreach ($pairs as $p)
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold">{{ $p['spent_branch'] }} → {{ $p['owed_branch'] }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ $p['entries'] }} entries · {{ $p['currency'] }}</p>
            <p class="mt-2 text-sm">Open: <strong>{{ number_format($p['open_total'], 2) }}</strong></p>
            <p class="text-xs text-gray-500">Settled: {{ number_format($p['settled_total'], 2) }}</p>
        </div>
        @endforeach
    </div>
    @endif

    <form method="POST" action="{{ route('finance.interbranch.settle') }}">
        @csrf
        <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left"><tr>
                    <th class="p-3 w-8"></th><th class="p-3">Date</th><th class="p-3">Reference</th>
                    <th class="p-3">Spent</th><th class="p-3">Owed</th><th class="p-3">Description</th>
                    <th class="p-3 text-right">Amount</th><th class="p-3">Status</th>
                </tr></thead>
                <tbody>
                @forelse ($entries as $e)
                    <tr class="border-t">
                        <td class="p-3">@if ($e->status === 'open')<input type="checkbox" name="entries[]" value="{{ $e->id }}">@endif</td>
                        <td class="p-3">{{ $e->entry_date->format('d M') }}</td>
                        <td class="p-3 font-mono text-xs">{{ $e->reference }}</td>
                        <td class="p-3">{{ $e->spentBranch?->name }}</td>
                        <td class="p-3">{{ $e->owedBranch?->name }}</td>
                        <td class="p-3">{{ $e->description }}</td>
                        <td class="p-3 text-right">{{ $e->currency }} {{ number_format($e->amount, 2) }}</td>
                        <td class="p-3"><span class="rounded-sm px-2 py-0.5 text-xs font-medium
                            {{ $e->status === 'settled' ? 'bg-emerald-100 text-emerald-700' : ($e->status === 'void' ? 'bg-gray-100 text-gray-500' : 'bg-amber-100 text-amber-700') }}">{{ $e->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="p-6 text-center text-gray-400">No inter-branch entries this month.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @can('finance.interbranch.manage')
        <button class="mt-3 rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Settle selected (post journal)</button>
        @endcan
    </form>

    @can('finance.interbranch.manage')
    <div class="mt-6 max-w-2xl rounded-sm border border-gray-200 bg-white p-5 shadow-sm">
        <h4 class="text-sm font-semibold">Record manual charge</h4>
        <form method="POST" action="{{ route('finance.interbranch.store') }}" class="mt-3 grid grid-cols-2 gap-3">
            @csrf
            <select name="spent_branch_id" required class="rounded-sm border-gray-300 text-sm">
                <option value="">Spent branch…</option>
                @foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
            </select>
            <select name="owed_branch_id" required class="rounded-sm border-gray-300 text-sm">
                <option value="">Owed branch…</option>
                @foreach ($branches as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach
            </select>
            <input name="description" required placeholder="Description" class="rounded-sm border-gray-300 text-sm">
            <div class="flex gap-2">
                <input name="amount" type="number" step="0.01" min="0.01" required placeholder="Amount" class="w-full rounded-sm border-gray-300 text-sm">
                <select name="currency" class="rounded-sm border-gray-300 text-sm">
                    @foreach (['AED','TZS','USD'] as $c)<option>{{ $c }}</option>@endforeach
                </select>
            </div>
            <button class="col-span-2 rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">Record</button>
        </form>
    </div>
    @endcan
</x-app-layout>
