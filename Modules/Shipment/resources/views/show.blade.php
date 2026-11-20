<x-app-layout>
    <x-slot name="header">Shipment {{ $s->reference }}</x-slot>
    <div class="space-y-5">
        @if(session('success'))<div class="p-3 bg-green-50 text-green-700 rounded text-sm">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="p-3 bg-red-50 text-red-700 rounded text-sm">{{ $errors->first() }}</div>@endif

        <div class="bg-white rounded-lg shadow-sm p-5 grid md:grid-cols-4 gap-4 text-sm">
            <div><p class="text-gray-500 text-xs">ROUTE</p><p class="font-semibold">{{ $s->origin_airport }} → {{ $s->destination_airport }}</p></div>
            <div><p class="text-gray-500 text-xs">STATUS</p><p class="font-semibold capitalize">{{ str_replace('_',' ',$s->status) }}</p></div>
            <div><p class="text-gray-500 text-xs">PACKAGES</p><p class="font-semibold">{{ $s->total_packages }}</p></div>
            <div><p class="text-gray-500 text-xs">CHARGEABLE</p><p class="font-semibold">{{ number_format($s->total_chargeable_weight,2) }} kg</p></div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-2">Readiness Checklist</h3>
            <div class="grid md:grid-cols-3 gap-2">
                @foreach($readiness as $check => $ok)
                <div class="flex items-center gap-2 text-sm">
                    <span class="w-4 h-4 rounded-full {{ $ok ? 'bg-green-500' : 'bg-red-400' }} inline-block"></span>
                    <span class="capitalize">{{ str_replace('_',' ',$check) }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-5">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-semibold">Packages ({{ $s->packages->count() }})</h3>
            </div>
            <form method="POST" action="{{ route('shipment.allocate', $s) }}" class="flex gap-2 mb-3">
                @csrf
                <input name="package_ids[]" placeholder="Package ID(s), comma-sep" class="rounded border-gray-300 text-sm flex-1"
                       onchange="this.value.split(',').forEach((v,i)=>{if(i>0){const n=this.cloneNode();n.value=v.trim();this.parentNode.insertBefore(n,this)}this.value=this.value.split(',')[0].trim()})">
                <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Allocate</button>
            </form>
            <table class="w-full text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-2 text-left">Reference</th><th>Customer</th><th>Gross</th><th>Chargeable</th><th>Status</th><th></th></tr></thead>
            <tbody class="divide-y">
                @forelse($s->packages as $p)
                <tr><td class="p-2 font-mono">{{ $p->reference }}</td><td>{{ $p->customer?->name ?? '—' }}</td>
                    <td class="text-center">{{ $p->gross_weight }}</td><td class="text-center">{{ $p->chargeable_weight }}</td>
                    <td class="text-center"><span class="px-2 py-0.5 rounded text-xs bg-indigo-100 text-indigo-700">{{ $p->status }}</span></td>
                    <td class="text-center"><form method="POST" action="{{ route('shipment.unallocate', [$s, $p->id]) }}">@csrf @method('DELETE')<button class="text-red-500 text-xs" onclick="return confirm('Remove package?')">Remove</button></form></td></tr>
                @empty
                <tr><td colspan="6" class="p-4 text-center text-gray-400">No packages allocated.</td></tr>
                @endforelse
            </tbody></table>
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            <div class="bg-white rounded-lg shadow-sm p-5">
                <h3 class="font-semibold mb-3">Status Transition</h3>
                <form method="POST" action="{{ route('shipment.transition', $s) }}" class="flex gap-2">
                    @csrf
                    <select name="to" class="rounded border-gray-300 text-sm flex-1">
                        @foreach(\Modules\Shipment\Models\Shipment::TRANSITIONS[$s->status] ?? [] as $to)
                        <option value="{{ $to }}">{{ str_replace('_',' ',$to) }}</option>
                        @endforeach
                    </select>
                    <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Apply</button>
                </form>

                <h3 class="font-semibold mt-5 mb-2">Timeline</h3>
                <div class="space-y-2 max-h-56 overflow-auto">
                    @foreach($s->events as $e)
                    <div class="text-xs flex gap-2"><span class="text-gray-400 shrink-0">{{ $e->created_at->format('d M H:i') }}</span><span>{{ $e->event }}</span><span class="text-gray-400">{{ $e->user?->name }}</span></div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-5">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Bookings</h3>
                    <form method="POST" action="{{ route('shipment.book', $s) }}" class="flex gap-2 mb-3">
                        @csrf
                        <select name="airline_id" required class="rounded border-gray-300 text-sm flex-1">
                            <option value="">Airline…</option>
                            @foreach(\Modules\Shipment\Models\Airline::where('status','active')->get() as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach
                        </select>
                        <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Book</button>
                    </form>
                    @foreach($s->bookings as $b)
                    <div class="border rounded p-2 mb-2 text-sm">
                        <div class="flex justify-between"><span class="font-mono">{{ $b->reference }}</span><span class="text-xs px-2 rounded {{ $b->status==='confirmed'?'bg-green-100 text-green-700':'bg-amber-100 text-amber-700' }}">{{ $b->status }}</span></div>
                        @if($b->status !== 'confirmed')
                        <form method="POST" action="{{ route('shipment.bookings.confirm', $b) }}" class="flex gap-1 mt-2">
                            @csrf
                            <input name="booking_ref" placeholder="Airline booking ref" class="rounded border-gray-300 text-xs flex-1">
                            <input name="flight_no" placeholder="Flight #" class="rounded border-gray-300 text-xs w-24">
                            <button class="px-2 py-1 bg-green-600 text-white rounded text-xs">Confirm</button>
                        </form>
                        @else<p class="text-xs text-gray-500 mt-1">Ref: {{ $b->booking_ref }} · Flight: {{ $b->flight_no }}</p>@endif
                    </div>
                    @endforeach
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Air Waybills</h3>
                    <form method="POST" action="{{ route('shipment.awb', $s) }}" class="flex gap-2 mb-3">
                        @csrf
                        <input name="awb_number" required placeholder="AWB number" class="rounded border-gray-300 text-sm flex-1">
                        <select name="kind" class="rounded border-gray-300 text-sm"><option value="hawb">HAWB</option><option value="mawb">MAWB</option></select>
                        <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Create</button>
                    </form>
                    @foreach($s->waybills as $a)
                    <div class="flex justify-between items-center border rounded p-2 mb-1 text-sm">
                        <span class="font-mono">{{ $a->awb_number }} <span class="text-xs text-gray-400">({{ $a->kind }} v{{ $a->version }})</span></span>
                        @if($a->status !== 'issued')
                        <form method="POST" action="{{ route('shipment.awbs.issue', $a) }}">@csrf<button class="text-xs px-2 py-1 bg-green-600 text-white rounded">Issue</button></form>
                        @else<span class="text-xs px-2 rounded bg-green-100 text-green-700">issued</span>@endif
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
