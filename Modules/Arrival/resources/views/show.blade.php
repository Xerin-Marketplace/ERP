<x-app-layout>
    <x-slot name="header">Arrival {{ $a->reference }}</x-slot>
    <div class="space-y-5">
        @if(session('success'))<div class="p-3 bg-green-50 text-green-700 rounded text-sm">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="p-3 bg-red-50 text-red-700 rounded text-sm">{{ $errors->first() }}</div>@endif

        <div class="bg-white rounded-lg shadow-sm p-5 grid md:grid-cols-5 gap-4 text-sm">
            <div><p class="text-gray-500 text-xs">SHIPMENT</p><p class="font-semibold">{{ $a->shipment?->reference }}</p></div>
            <div><p class="text-gray-500 text-xs">MAWB / FLIGHT</p><p class="font-semibold">{{ $a->mawb ?? '—' }} · {{ $a->flight_no ?? '—' }}</p></div>
            <div><p class="text-gray-500 text-xs">STATUS</p><p class="font-semibold capitalize">{{ str_replace('_',' ',$a->status) }}</p></div>
            <div><p class="text-gray-500 text-xs">PACKAGES</p><p class="font-semibold">{{ $a->received_packages }}/{{ $a->expected_packages }}</p></div>
            <div><p class="text-gray-500 text-xs">ACTUAL ARRIVAL</p><p class="font-semibold">{{ $a->actual_arrival?->format('d M Y H:i') ?? '—' }}</p></div>
        </div>

        <div class="grid md:grid-cols-2 gap-5">
            <div class="space-y-5">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Status &amp; Reconciliation</h3>
                    <form method="POST" action="{{ route('arrival.transition', $a) }}" class="flex gap-2 mb-3">@csrf
                        <select name="to" class="rounded border-gray-300 text-sm flex-1">
                            @foreach(\Modules\Arrival\Models\ShipmentArrival::TRANSITIONS[$a->status] ?? [] as $to)<option value="{{ $to }}">{{ str_replace('_',' ',$to) }}</option>@endforeach
                        </select><button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Apply</button></form>
                    <form method="POST" action="{{ route('arrival.reconcile', $a) }}" class="flex gap-2">@csrf
                        <input name="package_ids[]" placeholder="Scanned package IDs (comma-sep)" class="rounded border-gray-300 text-sm flex-1"
                            onchange="this.value.split(',').forEach((v,i)=>{if(i>0){const n=this.cloneNode();n.value=v.trim();this.parentNode.insertBefore(n,this)}this.value=this.value.split(',')[0].trim()})">
                        <button class="px-3 py-1 bg-amber-600 text-white rounded text-sm">Reconcile</button></form>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Expected Packages ({{ $a->shipment?->packages->count() }})</h3>
                    <div class="max-h-48 overflow-auto space-y-1">
                        @foreach($a->shipment?->packages ?? [] as $p)
                        <div class="flex justify-between text-sm border-b py-1"><span class="font-mono">{{ $p->reference }}</span>
                            <span class="text-xs px-2 rounded {{ $p->status==='received_tz'?'bg-green-100 text-green-700':'bg-gray-100' }}">{{ $p->status }}</span></div>
                        @endforeach
                    </div>
                    <form method="POST" action="{{ route('arrival.receive', $a) }}" class="flex gap-2 mt-3">@csrf
                        <input name="package_id" required placeholder="Pkg ID" class="rounded border-gray-300 text-sm w-24">
                        <select name="condition" class="rounded border-gray-300 text-sm"><option value="good">good</option><option value="damaged">damaged</option><option value="unidentified">unidentified</option></select>
                        <button class="px-3 py-1 bg-green-600 text-white rounded text-sm">Receive to Warehouse</button></form>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Discrepancies ({{ $a->discrepancies->count() }})</h3>
                    @foreach($a->discrepancies as $d)
                    <div class="border rounded p-2 mb-1 text-sm flex justify-between"><span>{{ $d->reference }} — {{ $d->kind }} {{ $d->package?->reference }}</span>
                        <span class="text-xs px-2 rounded {{ $d->status==='open'?'bg-red-100 text-red-700':'bg-green-100 text-green-700' }}">{{ $d->status }}</span></div>
                    @endforeach
                </div>
            </div>

            <div class="space-y-5">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Customs Clearance</h3>
                    @if($case = $a->customsCase)
                    <div class="text-sm mb-2"><span class="font-mono">{{ $case->reference }}</span> — <span class="px-2 py-0.5 rounded text-xs {{ $case->status==='released'?'bg-green-100 text-green-700':'bg-amber-100 text-amber-700' }}">{{ str_replace('_',' ',$case->status) }}</span>
                        @if($case->agent)<span class="text-gray-500">· {{ $case->agent->name }}</span>@endif</div>
                    <div class="text-xs text-gray-500 mb-2">Decl: {{ $case->declaration_ref ?? '—' }} · Duty: {{ number_format($case->duty_amount ?? 0) }} {{ $case->currency }}</div>
                    <form method="POST" action="{{ route('arrival.cases.transition', $case) }}" class="space-y-2">@csrf
                        <div class="flex gap-2"><select name="to" class="rounded border-gray-300 text-sm flex-1">
                            @foreach(\Modules\Arrival\Models\CustomsCase::TRANSITIONS[$case->status] ?? [] as $to)<option value="{{ $to }}">{{ str_replace('_',' ',$to) }}</option>@endforeach
                        </select><button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Move</button></div>
                        <div class="grid grid-cols-2 gap-2">
                            <input name="declaration_ref" placeholder="Decl ref" class="rounded border-gray-300 text-xs">
                            <input name="assessment_ref" placeholder="Assess ref" class="rounded border-gray-300 text-xs">
                            <input name="duty_amount" placeholder="Duty" class="rounded border-gray-300 text-xs">
                            <input name="tax_amount" placeholder="Tax" class="rounded border-gray-300 text-xs">
                            <input name="payment_evidence" placeholder="Payment evidence" class="rounded border-gray-300 text-xs">
                            <input name="release_evidence" placeholder="Release evidence" class="rounded border-gray-300 text-xs">
                        </div></form>
                    @else
                    <form method="POST" action="{{ route('arrival.customs.open', $a) }}" class="flex gap-2">@csrf
                        <select name="agent_id" class="rounded border-gray-300 text-sm flex-1"><option value="">Agent…</option>
                            @foreach(\Modules\Arrival\Models\ClearingAgent::where('status','active')->get() as $ag)<option value="{{ $ag->id }}">{{ $ag->name }}</option>@endforeach</select>
                        <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Open Case</button></form>
                    @endif
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Charges</h3>
                    <form method="POST" action="{{ route('arrival.charge', $a) }}" class="flex gap-2 mb-2">@csrf
                        <select name="category" required class="rounded border-gray-300 text-xs"><option value="customs_duty">duty</option><option value="import_tax">tax</option><option value="agent_fee">agent</option><option value="airport_handling">handling</option><option value="storage">storage</option><option value="other">other</option></select>
                        <input name="amount" required placeholder="Amount" class="rounded border-gray-300 text-xs w-24">
                        <input name="currency" value="TZS" class="rounded border-gray-300 text-xs w-16">
                        <button class="px-2 py-1 bg-indigo-600 text-white rounded text-xs">Add</button></form>
                    @foreach($a->charges as $ch)
                    <div class="flex justify-between text-sm border-b py-1"><span class="capitalize">{{ str_replace('_',' ',$ch->category) }}</span>
                        <span>{{ number_format($ch->amount,2) }} {{ $ch->currency }} <span class="text-xs text-gray-400">({{ $ch->payment_status }})</span></span></div>
                    @endforeach
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Airport Collection</h3>
                    @foreach($a->collections as $col)
                    <div class="border rounded p-2 mb-2 text-sm">
                        <div class="flex justify-between"><span class="font-mono">{{ $col->reference }}</span><span class="text-xs px-2 rounded bg-indigo-100 text-indigo-700">{{ $col->status }}</span></div>
                        <div class="text-xs text-gray-500">{{ $col->collected_packages }}/{{ $col->expected_packages }} collected · release: {{ $col->airport_release_ref ?? '—' }}</div>
                        @if($col->status==='requested')
                        <form method="POST" action="{{ route('arrival.collections.approve', $col) }}" class="flex gap-1 mt-1">@csrf
                            <input name="airport_release_ref" placeholder="Release ref" class="rounded border-gray-300 text-xs flex-1">
                            <button class="px-2 py-0.5 bg-green-600 text-white rounded text-xs">Approve</button></form>
                        @elseif($col->status==='approved')
                        <form method="POST" action="{{ route('arrival.collections.confirm', $col) }}" class="flex gap-1 mt-1">@csrf
                            <input name="package_ids[]" placeholder="Scanned pkg IDs" class="rounded border-gray-300 text-xs flex-1">
                            <button class="px-2 py-0.5 bg-green-600 text-white rounded text-xs">Confirm</button></form>
                        @endif
                    </div>
                    @endforeach
                    <form method="POST" action="{{ route('arrival.collection.request', $a) }}" class="flex gap-2">@csrf
                        <input name="vehicle" placeholder="Vehicle" class="rounded border-gray-300 text-xs w-28">
                        <input name="driver" placeholder="Driver" class="rounded border-gray-300 text-xs w-28">
                        <button class="px-3 py-1 bg-indigo-600 text-white rounded text-xs">Request Collection</button></form>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Timeline</h3>
                    <div class="space-y-1 max-h-48 overflow-auto">
                        @foreach($a->events as $e)
                        <div class="text-xs flex gap-2"><span class="text-gray-400 shrink-0">{{ $e->created_at->format('d M H:i') }}</span><span>{{ $e->event }}</span><span class="text-gray-400">{{ $e->source }}</span></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
