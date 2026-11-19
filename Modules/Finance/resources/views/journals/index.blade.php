<x-app-layout>
    <x-slot name="header">{{ __('Journals') }}</x-slot>
    @can('finance.journals.create')
    <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm mb-4" x-data="{ lines: [{account_id:'',debit:0,credit:0,description:''},{account_id:'',debit:0,credit:0,description:''}] }">
        <h3 class="text-sm font-semibold mb-2">New Journal Entry</h3>
        <form method="POST" action="{{ route('finance.journals.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-3 gap-3">
                <select name="period_id" class="rounded border-gray-300 text-sm" required>
                    <option value="">Period…</option>
                    @foreach ($openPeriods as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach
                </select>
                <input type="date" name="journal_date" class="rounded border-gray-300 text-sm" required>
                <input type="text" name="memo" placeholder="Memo" class="rounded border-gray-300 text-sm">
            </div>
            <template x-for="(line, i) in lines" :key="i">
                <div class="grid grid-cols-12 gap-2">
                    <select :name="`lines[${i}][account_id]`" class="col-span-5 rounded border-gray-300 text-sm" required>
                        <option value="">Account…</option>
                        @foreach ($accounts as $a)<option value="{{ $a->id }}">{{ $a->code }} — {{ $a->name }}</option>@endforeach
                    </select>
                    <input type="number" step="0.01" min="0" :name="`lines[${i}][debit]`" placeholder="Debit" class="col-span-2 rounded border-gray-300 text-sm">
                    <input type="number" step="0.01" min="0" :name="`lines[${i}][credit]`" placeholder="Credit" class="col-span-2 rounded border-gray-300 text-sm">
                    <input type="text" :name="`lines[${i}][description]`" placeholder="Description" class="col-span-3 rounded border-gray-300 text-sm">
                </div>
            </template>
            <div class="flex gap-2">
                <button type="button" @click="lines.push({account_id:'',debit:0,credit:0,description:''})" class="text-xs text-indigo-600">+ Add line</button>
                <button class="ml-auto rounded bg-indigo-600 px-4 py-1.5 text-sm text-white">Create Draft</button>
            </div>
        </form>
    </div>
    @endcan
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left"><tr>
                <th class="p-3">Reference</th><th class="p-3">Date</th><th class="p-3">Type</th><th class="p-3">Period</th>
                <th class="p-3 text-right">Debit</th><th class="p-3 text-right">Credit</th><th class="p-3">Status</th><th class="p-3"></th>
            </tr></thead>
            <tbody>
            @foreach ($journals as $j)
                <tr class="border-t">
                    <td class="p-3 font-mono text-xs">{{ $j->reference }}</td>
                    <td class="p-3">{{ $j->journal_date->format('d M Y') }}</td>
                    <td class="p-3">{{ $j->type }}</td>
                    <td class="p-3">{{ $j->period->name }}</td>
                    <td class="p-3 text-right">{{ number_format($j->lines->sum('debit'), 2) }}</td>
                    <td class="p-3 text-right">{{ number_format($j->lines->sum('credit'), 2) }}</td>
                    <td class="p-3"><span class="rounded px-2 py-0.5 text-xs {{ $j->status==='posted' ? 'bg-green-100 text-green-700' : ($j->status==='reversed' ? 'bg-gray-200' : 'bg-amber-100 text-amber-700') }}">{{ $j->status }}</span></td>
                    <td class="p-3 text-right whitespace-nowrap">
                        @if ($j->status === 'draft') @can('finance.journals.submit')
                            <form method="POST" action="{{ route('finance.journals.submit', $j) }}" class="inline">@csrf<button class="text-xs text-indigo-600">Submit</button></form>@endcan @endif
                        @if ($j->status === 'submitted') @can('finance.journals.approve')
                            <form method="POST" action="{{ route('finance.journals.approve', $j) }}" class="inline">@csrf<button class="text-xs text-green-600">Approve</button></form>@endcan @endif
                        @if (in_array($j->status, ['approved','submitted','draft'])) @can('finance.journals.post')
                            <form method="POST" action="{{ route('finance.journals.post', $j) }}" class="inline">@csrf<button class="text-xs text-indigo-600">Post</button></form>@endcan @endif
                        @if ($j->status === 'posted') @can('finance.journals.reverse')
                            <form method="POST" action="{{ route('finance.journals.reverse', $j) }}" class="inline" onsubmit="return confirm('Reverse this journal?')">@csrf<button class="text-xs text-red-600">Reverse</button></form>@endcan @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $journals->links() }}</div>
    </div>
</x-app-layout>
