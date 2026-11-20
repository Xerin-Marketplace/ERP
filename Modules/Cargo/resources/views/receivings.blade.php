<x-app-layout>
    <x-slot name="header">Cargo Receiving</x-slot>
    <div class="space-y-4" x-data="{open:false}">
        @can('cargo.receiving.create')
        <button @click="open=!open" class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm font-medium">+ Start Receiving</button>
        <form x-show="open" x-cloak method="POST" action="{{ route('cargo.receivings.start') }}" class="bg-white rounded-md border border-gray-200 p-5 grid md:grid-cols-3 gap-4">
            @csrf
            <div><label class="text-xs font-medium text-gray-600">Pre-alert</label><select name="pre_alert_id" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="">— none —</option>@foreach($alerts as $a)<option value="{{ $a->id }}">{{ $a->reference }} · {{ $a->customer?->name ?? $a->supplier_name }}</option>@endforeach</select></div>
            <div><label class="text-xs font-medium text-gray-600">Customer</label><select name="customer_id" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="">— unidentified —</option>@foreach(\Modules\Commercial\Models\Customer::orderBy('name')->limit(200)->get() as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
            <div><label class="text-xs font-medium text-gray-600">Supplier / Sender</label><input name="supplier_name" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Branch *</label><select name="branch_id" required class="mt-1 w-full rounded-sm border-gray-200 text-sm">@foreach(\Modules\Organization\Models\Branch::orderBy('name')->get() as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
            <div><label class="text-xs font-medium text-gray-600">Expected pkgs</label><input type="number" name="expected_packages" min="0" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <div><label class="text-xs font-medium text-gray-600">Delivery note ref</label><input name="delivery_note_ref" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="unidentified" value="1" class="rounded border-gray-300">Unidentified cargo</label>
            <div class="md:col-span-3"><button class="px-5 py-2 bg-xerin-700 text-white rounded-sm text-sm">Start Receiving Session</button></div>
        </form>
        @endcan
        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2.5 text-left">Ref</th><th class="px-3 py-2.5 text-left">Customer</th><th class="px-3 py-2.5 text-left">Pkgs</th><th class="px-3 py-2.5 text-left">Outcome</th><th class="px-3 py-2.5 text-left">Status</th><th class="px-5 py-2.5"></th></tr></thead>
            <tbody class="divide-y divide-gray-100">@forelse($receivings as $r)
                <tr class="hover:bg-gray-50"><td class="px-5 py-3 font-mono text-xs">{{ $r->reference }}</td><td class="px-3 py-3">{{ $r->unidentified?'⚠ Unidentified':($r->customer?->name ?? '—') }}</td><td class="px-3 py-3">{{ $r->actual_packages }}/{{ $r->expected_packages }}</td><td class="px-3 py-3 capitalize">{{ $r->outcome ?? '—' }}</td><td class="px-3 py-3"><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ str_replace('_',' ',$r->status) }}</span></td><td class="px-5 py-3 text-right"><a href="{{ route('cargo.receivings.show',$r) }}" class="text-xerin-700 text-sm hover:underline">Open</a></td></tr>
            @empty <tr><td colspan="6" class="px-5 py-8 text-center text-gray-400">No receivings.</td></tr> @endforelse</tbody></table></div>
        {{ $receivings->links() }}
    </div>
</x-app-layout>
