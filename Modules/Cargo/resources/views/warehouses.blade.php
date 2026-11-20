<x-app-layout>
    <x-slot name="header">Warehouses & Locations</x-slot>
    <div class="space-y-6">
        @foreach($warehouses as $w)
        <div class="bg-white rounded-md border border-gray-200">
            <div class="px-5 py-3 border-b border-gray-100 flex justify-between"><span class="font-semibold">{{ $w->name }} <span class="font-mono text-xs text-gray-500">{{ $w->code }}</span></span><span class="text-xs text-gray-500">{{ $w->branch?->name }}</span></div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 p-5">
                @foreach($w->locations as $l)
                <div class="border border-gray-200 rounded-sm p-3 text-sm">
                    <div class="font-mono text-xs font-semibold">{{ $l->code }}</div>
                    <div class="text-xs text-gray-500 capitalize">{{ $l->type }}</div>
                    @if($l->capacity)<div class="mt-1 text-xs">{{ $l->occupied() }}/{{ $l->capacity }} ({{ $l->occupancyPct() }}%)</div>
                    <div class="mt-1 h-1.5 bg-gray-100 rounded-full"><div class="h-1.5 rounded-full {{ $l->occupancyPct() > 85 ? 'bg-red-500' : 'bg-xerin-500' }}" style="width:{{ $l->occupancyPct() }}%"></div></div>@endif
                </div>
                @endforeach
            </div>
            @can('warehouse.locations.manage')
            <form method="POST" action="{{ route('cargo.locations.store') }}" class="px-5 pb-5 flex flex-wrap gap-2">@csrf
                <input type="hidden" name="warehouse_id" value="{{ $w->id }}">
                <input name="code" required placeholder="Code e.g. C-02-01" class="rounded-sm border-gray-200 text-sm w-36">
                <select name="type" class="rounded-sm border-gray-200 text-sm">@foreach(['receiving','inspection','storage','hold','repacking','dispatch','quarantine'] as $t)<option>{{ $t }}</option>@endforeach</select>
                <input name="capacity" type="number" min="0" placeholder="Capacity" class="rounded-sm border-gray-200 text-sm w-24">
                <button class="px-3 py-2 bg-gray-700 text-white rounded-sm text-sm">Add Location</button></form>
            @endcan
        </div>
        @endforeach
    </div>
</x-app-layout>
