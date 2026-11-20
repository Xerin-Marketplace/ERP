<x-app-layout>
    <x-slot name="header">Receiving {{ $receiving->reference }}</x-slot>
    <div class="space-y-6">
        <div class="bg-white rounded-md border border-gray-200 p-5 flex flex-wrap justify-between gap-4">
            <div>
                <div class="flex items-center gap-3"><h2 class="text-lg font-semibold">{{ $receiving->reference }}</h2><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ str_replace('_',' ',$receiving->status) }}</span>@if($receiving->unidentified)<span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">UNIDENTIFIED</span>@endif</div>
                <div class="mt-1 text-sm text-gray-600">{{ $receiving->customer?->name ?? $receiving->supplier_name ?? '—' }} · {{ $receiving->branch?->name }} · by {{ $receiving->receiver?->name }}</div>
                <div class="mt-1 text-sm">{{ $receiving->actual_packages }} / {{ $receiving->expected_packages }} packages</div>
            </div>
            @if($receiving->status==='in_progress' && auth()->user()->can('cargo.receiving.confirm'))
                <form method="POST" action="{{ route('cargo.receivings.confirm',$receiving) }}" onsubmit="return confirm('Confirm receiving?')">@csrf<button class="px-4 py-2 bg-emerald-700 text-white rounded-sm text-sm">Confirm Receiving</button></form>
            @endif
        </div>
        @if($receiving->status==='in_progress')
        @can('cargo.receiving.create')
        <form method="POST" action="{{ route('cargo.receivings.packages',$receiving) }}" class="bg-white rounded-md border border-gray-200 p-5 flex flex-wrap items-end gap-3">
            @csrf
            <div><label class="text-xs font-medium text-gray-600">Add packages (count)</label><input type="number" name="count" min="1" value="1" class="mt-1 w-28 rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Type</label><select name="package_type" class="mt-1 rounded-sm border-gray-200 text-sm">@foreach(['carton','wooden_box','pallet','bag','crate','other'] as $t)<option>{{ $t }}</option>@endforeach</select></div>
            <div><label class="text-xs font-medium text-gray-600">Handling</label><select name="handling" class="mt-1 rounded-sm border-gray-200 text-sm"><option value="normal">normal</option><option>fragile</option><option>liquid</option><option>battery</option><option>perishable</option><option>dangerous</option></select></div>
            <div class="flex-1 min-w-40"><label class="text-xs font-medium text-gray-600">Goods description</label><input name="goods_description" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <button class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm">Register</button>
        </form>
        @endcan
        @endif
        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2.5 text-left">Package Ref</th><th class="px-3 py-2.5 text-left">Seq</th><th class="px-3 py-2.5 text-left">Type</th><th class="px-3 py-2.5 text-left">Weight</th><th class="px-3 py-2.5 text-left">Inspection</th><th class="px-3 py-2.5 text-left">Location</th><th class="px-3 py-2.5 text-left">Status</th></tr></thead>
            <tbody class="divide-y divide-gray-100">@forelse($receiving->packages as $p)
                <tr class="hover:bg-gray-50"><td class="px-5 py-3 font-mono text-xs"><a class="text-xerin-700 hover:underline" href="{{ route('cargo.packages.show',$p) }}">{{ $p->reference }}</a></td><td class="px-3 py-3">{{ $p->sequence }}</td><td class="px-3 py-3">{{ $p->package_type }}</td><td class="px-3 py-3">{{ $p->chargeable_weight ?? '—' }} kg</td><td class="px-3 py-3 capitalize">{{ str_replace('_',' ',$p->inspection_status) }}</td><td class="px-3 py-3">{{ $p->location?->code ?? '—' }}</td><td class="px-3 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ str_replace('_',' ',$p->status) }}</span></td></tr>
            @empty <tr><td colspan="7" class="px-5 py-8 text-center text-gray-400">No packages registered yet.</td></tr> @endforelse</tbody></table></div>
    </div>
</x-app-layout>
