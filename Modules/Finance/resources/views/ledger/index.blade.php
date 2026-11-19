<x-app-layout>
    <x-slot name="header">{{ __('General Ledger') }}</x-slot>
    <form method="GET" class="mb-4 flex gap-2 items-end">
        <select name="account_id" class="rounded border-gray-300 text-sm" required>
            <option value="">Select account…</option>
            @foreach ($accounts as $a)<option value="{{ $a->id }}" @selected(request('account_id') == $a->id)>{{ $a->code }} — {{ $a->name }}</option>@endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="rounded border-gray-300 text-sm">
        <input type="date" name="to" value="{{ request('to') }}" class="rounded border-gray-300 text-sm">
        <button class="rounded bg-indigo-600 px-4 py-1.5 text-sm text-white">View</button>
    </form>
    @if ($data)
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <div class="p-3 border-b text-sm font-semibold">{{ $data['account']->code }} — {{ $data['account']->name }}
            <span class="float-right text-gray-500">Opening: {{ number_format($data['opening'], 2) }} · Closing: {{ number_format($data['closing'], 2) }}</span>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left"><tr><th class="p-3">Date</th><th class="p-3">Reference</th><th class="p-3">Description</th><th class="p-3 text-right">Debit</th><th class="p-3 text-right">Credit</th><th class="p-3 text-right">Balance</th></tr></thead>
            <tbody>
            @foreach ($data['rows'] as $row)
                <tr class="border-t">
                    <td class="p-3">{{ $row['entry']->entry_date->format('d M Y') }}</td>
                    <td class="p-3 font-mono text-xs">{{ $row['entry']->reference }}</td>
                    <td class="p-3">{{ $row['entry']->description }}</td>
                    <td class="p-3 text-right">{{ number_format($row['entry']->debit, 2) }}</td>
                    <td class="p-3 text-right">{{ number_format($row['entry']->credit, 2) }}</td>
                    <td class="p-3 text-right font-semibold">{{ number_format($row['balance'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</x-app-layout>
