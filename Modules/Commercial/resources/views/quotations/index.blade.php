<x-app-layout>
    <x-slot name="header">Quotations</x-slot>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center gap-3">
            <form method="GET" class="flex gap-2 flex-1 min-w-60">
                <input name="q" value="{{ request('q') }}" placeholder="Search number, customer…" class="flex-1 rounded-sm border-gray-200 text-sm">
                <select name="status" class="rounded-sm border-gray-200 text-sm"><option value="">All statuses</option>@foreach(\Modules\Commercial\Models\Quotation::STATUSES as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
                <button class="px-4 py-2 bg-xerin-600 text-white rounded-sm text-sm">Filter</button>
            </form>
            @can('quotation.create')<a href="{{ route('commercial.quotations.create') }}" class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm font-medium">+ New Quotation</a>@endcan
        </div>
        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2.5 text-left">Number</th><th class="px-3 py-2.5 text-left">Customer</th><th class="px-3 py-2.5 text-left">Route</th><th class="px-3 py-2.5 text-left">Status</th><th class="px-3 py-2.5 text-left">Valid until</th><th class="px-5 py-2.5 text-right">Total</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($quotations as $q)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3 font-mono text-xs"><a class="text-xerin-700 hover:underline" href="{{ route('commercial.quotations.show', $q) }}">{{ $q->number }}</a>@if($q->version>1)<span class="text-gray-400"> v{{ $q->version }}</span>@endif</td>
                            <td class="px-3 py-3">{{ $q->customer?->name ?? '—' }}</td>
                            <td class="px-3 py-3 text-xs">{{ $q->origin ?? '?' }} → {{ $q->destination ?? '?' }}</td>
                            <td class="px-3 py-3"><span class="px-2 py-0.5 rounded-full text-xs capitalize {{ $q->status==='accepted'?'bg-emerald-50 text-emerald-700':($q->status==='rejected'?'bg-red-50 text-red-600':'bg-gray-100') }}">{{ str_replace('_',' ',$q->status) }}</span></td>
                            <td class="px-3 py-3 text-xs {{ $q->isExpired() ? 'text-red-600' : '' }}">{{ $q->valid_until?->format('d M Y') ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-medium">{{ number_format($q->total, 2) }} {{ $q->currency }}</td>
                        </tr>
                    @empty <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No quotations found.</td></tr> @endforelse
                </tbody>
            </table>
        </div>
        {{ $quotations->links() }}
    </div>
</x-app-layout>
