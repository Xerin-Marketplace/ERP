<x-app-layout>
    <x-slot name="header">Packages</x-slot>
    <div class="space-y-4">
        <form method="GET" class="flex gap-2"><input name="q" value="{{ request('q') }}" placeholder="Search package ref or description…" class="flex-1 rounded-sm border-gray-200 text-sm">
        <select name="status" class="rounded-sm border-gray-200 text-sm"><option value="">All statuses</option>@foreach(\Modules\Cargo\Models\CargoPackage::STATUSES as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
        <button class="px-4 py-2 bg-xerin-600 text-white rounded-sm text-sm">Search</button></form>
        <div class="bg-white rounded-md border border-gray-200 overflow-x-auto"><table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase"><tr><th class="px-5 py-2.5 text-left">Reference</th><th class="px-3 py-2.5 text-left">Customer</th><th class="px-3 py-2.5 text-left">Cargo</th><th class="px-3 py-2.5 text-left">Chargeable</th><th class="px-3 py-2.5 text-left">Location</th><th class="px-3 py-2.5 text-left">Status</th><th class="px-5 py-2.5"></th></tr></thead>
            <tbody class="divide-y divide-gray-100">@forelse($packages as $p)
                <tr class="hover:bg-gray-50"><td class="px-5 py-3 font-mono text-xs">{{ $p->reference }}</td><td class="px-3 py-3">{{ $p->unidentified?'⚠ Unidentified':($p->customer?->name ?? '—') }}</td><td class="px-3 py-3 text-xs">{{ Str::limit($p->goods_description,30) }}</td><td class="px-3 py-3">{{ $p->chargeable_weight ?? '—' }} kg</td><td class="px-3 py-3">{{ $p->location?->code ?? '—' }}</td><td class="px-3 py-3"><span class="px-2 py-0.5 rounded-full text-xs {{ $p->status==='on_hold'?'bg-red-50 text-red-600':'bg-gray-100' }} capitalize">{{ str_replace('_',' ',$p->status) }}</span></td><td class="px-5 py-3 text-right"><a href="{{ route('cargo.packages.show',$p) }}" class="text-xerin-700 text-sm hover:underline">Open</a></td></tr>
            @empty <tr><td colspan="7" class="px-5 py-8 text-center text-gray-400">No packages.</td></tr> @endforelse</tbody></table></div>
        {{ $packages->links() }}
    </div>
</x-app-layout>
