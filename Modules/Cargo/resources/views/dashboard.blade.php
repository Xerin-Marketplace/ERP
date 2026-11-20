<x-app-layout>
    <x-slot name="header">Cargo Operations — Dubai Warehouse</x-slot>
    <div class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach ([['Expected Today',$stats['expected_today']],['Received Today',$stats['received_today']],['Packages Today',$stats['packages_today']],['Pending Receiving',$stats['pending_receiving']],['Pending Weighing',$stats['pending_weighing']],['Pending Inspection',$stats['pending_inspection']],['On Hold',$stats['on_hold']],['Repack Required',$stats['repack_required']],['In Storage',$stats['in_storage']],['Ready',$stats['ready']],['Unidentified',$stats['unidentified']],['Open Incidents',$stats['open_incidents']]] as [$l,$v])
                <div class="bg-white rounded-md border border-gray-200 p-4"><div class="text-xs font-medium text-gray-500 uppercase">{{ $l }}</div><div class="mt-1 text-2xl font-semibold {{ in_array($l,['On Hold','Unidentified','Open Incidents','Repack Required']) && $v ? 'text-red-600' : 'text-gray-900' }}">{{ $v }}</div></div>
            @endforeach
        </div>
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-md border border-gray-200">
                <div class="px-5 py-3 border-b border-gray-100 font-semibold">Expected Deliveries</div>
                <div class="divide-y divide-gray-100">
                    @forelse($alerts as $a)
                        <div class="px-5 py-2.5 text-sm flex justify-between"><span><span class="font-mono text-xs">{{ $a->reference }}</span> {{ $a->customer?->name ?? $a->supplier_name }}</span><span class="text-xs text-gray-500">{{ $a->expected_date?->format('d M') }} · {{ $a->expected_packages }} pkg</span></div>
                    @empty <p class="px-5 py-4 text-sm text-gray-400">No pending pre-alerts.</p> @endforelse
                </div>
            </div>
            <div class="bg-white rounded-md border border-gray-200">
                <div class="px-5 py-3 border-b border-gray-100 font-semibold">Recent Receivings</div>
                <div class="divide-y divide-gray-100">
                    @forelse($recent as $r)
                        <a href="{{ route('cargo.receivings.show',$r) }}" class="px-5 py-2.5 text-sm flex justify-between hover:bg-gray-50"><span class="font-mono text-xs">{{ $r->reference }}</span><span class="text-xs">{{ $r->actual_packages }}/{{ $r->expected_packages }} pkg · <span class="capitalize">{{ $r->status }}</span></span></a>
                    @empty <p class="px-5 py-4 text-sm text-gray-400">None yet.</p> @endforelse
                </div>
            </div>
        </div>
        <div class="bg-white rounded-md border border-gray-200 p-5">
            <h3 class="font-semibold mb-3">Packages by Status</h3>
            <div class="flex flex-wrap gap-2">@foreach($stats['by_status'] as $s=>$n)<span class="px-3 py-1 bg-gray-100 rounded-full text-xs capitalize">{{ str_replace('_',' ',$s) }}: {{ $n }}</span>@endforeach</div>
        </div>
    </div>
</x-app-layout>
