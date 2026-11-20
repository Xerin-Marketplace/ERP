<x-app-layout>
    <x-slot name="header">Supplier Pre-Alerts</x-slot>
    <div class="space-y-4" x-data="{open:false}">
        @can('cargo.pre_alerts.create')
        <button @click="open=!open" class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm font-medium">+ New Pre-Alert</button>
        <form x-show="open" x-cloak method="POST" action="{{ route('cargo.pre-alerts.store') }}" class="bg-white rounded-md border border-gray-200 p-5 grid md:grid-cols-3 gap-4">
            @csrf
            <div><label class="text-xs font-medium text-gray-600">Customer</label><select name="customer_id" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="">—</option>@foreach(\Modules\Commercial\Models\Customer::orderBy('name')->limit(200)->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
            <div><label class="text-xs font-medium text-gray-600">Supplier / Sender</label><input name="supplier_name" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Supplier ref (PO/invoice)</label><input name="supplier_ref" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Branch *</label><select name="branch_id" required class="mt-1 w-full rounded-sm border-gray-200 text-sm">@foreach(\Modules\Organization\Models\Branch::orderBy('name')->get() as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div><label class="text-xs font-medium text-gray-600">Expected date</label><input type="date" name="expected_date" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Expected packages</label><input type="number" name="expected_packages" min="0" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div class="md:col-span-3"><label class="text-xs font-medium text-gray-600">Cargo description</label><input name="cargo_description" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div class="md:col-span-3"><button class="px-5 py-2 bg-xerin-700 text-white rounded-sm text-sm">Create Pre-Alert</button></div>
        </form>
        @endcan
        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2.5 text-left">Ref</th><th class="px-3 py-2.5 text-left">Customer/Supplier</th><th class="px-3 py-2.5 text-left">Expected</th><th class="px-3 py-2.5 text-left">Packages</th><th class="px-3 py-2.5 text-left">Status</th></tr></thead>
            <tbody class="divide-y divide-gray-100">@forelse($alerts as $a)
                <tr class="hover:bg-gray-50"><td class="px-5 py-3 font-mono text-xs">{{ $a->reference }}</td><td class="px-3 py-3">{{ $a->customer?->name ?? $a->supplier_name }}</td><td class="px-3 py-3 text-xs">{{ $a->expected_date?->format('d M Y') }}</td><td class="px-3 py-3">{{ $a->expected_packages }}</td><td class="px-3 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ str_replace('_',' ',$a->status) }}</span></td></tr>
            @empty <tr><td colspan="5" class="px-5 py-8 text-center text-gray-400">No pre-alerts.</td></tr> @endforelse</tbody></table></div>
        {{ $alerts->links() }}
    </div>
</x-app-layout>
