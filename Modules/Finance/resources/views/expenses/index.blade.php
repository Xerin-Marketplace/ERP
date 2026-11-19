<x-app-layout>
    <x-slot name="header">{{ __('Expense Management') }}</x-slot>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left"><tr><th class="p-3">Ref</th><th class="p-3">Employee</th><th class="p-3">Category</th><th class="p-3">Date</th><th class="p-3">Description</th><th class="p-3 text-right">Amount</th><th class="p-3">Status</th><th class="p-3"></th></tr></thead>
            <tbody>
            @foreach ($claims as $c)
                <tr class="border-t">
                    <td class="p-3 font-mono text-xs">{{ $c->reference }}</td>
                    <td class="p-3">{{ $c->employee?->full_name ?? $c->employee?->first_name ?? '—' }}</td>
                    <td class="p-3">{{ $c->category->name }}</td>
                    <td class="p-3">{{ $c->expense_date->format('d M Y') }}</td>
                    <td class="p-3">{{ Str::limit($c->description, 40) }}</td>
                    <td class="p-3 text-right">{{ number_format($c->amount, 2) }}</td>
                    <td class="p-3"><span class="rounded px-2 py-0.5 text-xs {{ in_array($c->status,['posted','paid']) ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">{{ $c->status }}</span></td>
                    <td class="p-3 text-right">@if ($c->status === 'approved')
                        <form method="POST" action="{{ route('finance.expenses.post', $c) }}" class="inline">@csrf<button class="text-xs text-indigo-600">Post</button></form>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="p-3">{{ $claims->links() }}</div>
    </div>
</x-app-layout>
