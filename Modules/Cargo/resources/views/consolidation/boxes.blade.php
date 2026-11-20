<x-app-layout>
    <x-slot name="header">Consolidation Boxes</x-slot>
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-gray-500">{{ $openCount }} open box(es)</p>
        @can('cargo.consolidation.manage')
        <form method="POST" action="{{ route('cargo.consolidation.boxes.store') }}" class="flex gap-2">
            @csrf
            <select name="branch_id" required class="rounded-sm border-gray-300 text-sm">
                @foreach (\Modules\Organization\Models\Branch::active()->orderBy('name')->get() as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                @endforeach
            </select>
            <input name="target_weight" type="number" step="0.1" placeholder="Target KG (23)" class="w-32 rounded-sm border-gray-300 text-sm">
            <button class="rounded-sm bg-xerin-700 px-4 py-2 text-sm font-semibold text-white">New Box</button>
        </form>
        @endcan
    </div>
    <div class="rounded-sm border border-gray-200 bg-white shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="border-b bg-gray-50 text-left text-xs text-gray-500">
                <th class="px-4 py-2">Box ID</th><th class="px-4 py-2">Branch</th><th class="px-4 py-2">Lines</th>
                <th class="px-4 py-2">Weight</th><th class="px-4 py-2">Seal</th><th class="px-4 py-2">Trip</th>
                <th class="px-4 py-2">Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($boxes as $box)
                <tr class="border-b border-gray-50">
                    <td class="px-4 py-2 font-mono text-xs font-semibold">{{ $box->reference }}</td>
                    <td class="px-4 py-2 text-xs">{{ $box->branch->name ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $box->lines->count() }}</td>
                    <td class="px-4 py-2 text-xs">{{ $box->actual_weight ?? $box->lines->sum('weight') }} / {{ $box->target_weight }} KG</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $box->seal_number ?? '—' }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $box->trip->reference ?? '—' }}</td>
                    <td class="px-4 py-2"><span class="rounded-sm bg-{{ $box->status === 'open' ? 'amber' : ($box->status === 'departed' ? 'xerin' : 'gray') }}-50 px-2 py-0.5 text-xs font-semibold">{{ ucfirst(str_replace('_',' ',$box->status)) }}</span></td>
                    <td class="px-4 py-2"><a href="{{ route('cargo.consolidation.boxes.show', $box) }}" class="text-xs font-semibold text-xerin-700">Open</a></td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-gray-400">No boxes yet.</td></tr>
            @endforelse
            </tbody></table>
        <div class="p-4">{{ $boxes->links() }}</div>
    </div>
</x-app-layout>
