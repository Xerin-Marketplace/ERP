<x-app-layout>
    <x-slot name="header">Delivery {{ $o->reference }}</x-slot>
    <div class="space-y-5">
        @if(session('success'))<div class="p-3 bg-green-50 text-green-700 rounded text-sm">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="p-3 bg-red-50 text-red-700 rounded text-sm">{{ $errors->first() }}</div>@endif
        <div class="bg-white rounded-lg shadow-sm p-5 grid md:grid-cols-5 gap-4 text-sm">
            <div><p class="text-gray-500 text-xs">CUSTOMER</p><p class="font-semibold">{{ $o->customer?->name }}</p></div>
            <div><p class="text-gray-500 text-xs">STATUS</p><p class="font-semibold capitalize">{{ str_replace('_',' ',$o->status) }}</p></div>
            <div><p class="text-gray-500 text-xs">DRIVER</p><p class="font-semibold">{{ $o->driver?->name ?? '—' }}</p></div>
            <div><p class="text-gray-500 text-xs">FEE / COD</p><p class="font-semibold">{{ $o->fee ? number_format($o->fee) : '—' }} / {{ $o->cod_amount ? number_format($o->cod_amount) : '—' }}</p></div>
            <div><p class="text-gray-500 text-xs">PACKAGES</p><p class="font-semibold">{{ $o->packages->count() }}</p></div>
        </div>
        <div class="grid md:grid-cols-2 gap-5">
            <div class="space-y-5">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Dispatch</h3>
                    <form method="POST" action="{{ route('delivery.assign', $o) }}" class="flex gap-2 mb-2">@csrf
                        <select name="driver_id" required class="rounded border-gray-300 text-sm flex-1"><option value="">Driver…</option>
                            @foreach($drivers as $d)<option value="{{ $d->id }}">{{ $d->name }} ({{ $d->availability }})</option>@endforeach</select>
                        <select name="vehicle_id" class="rounded border-gray-300 text-sm"><option value="">Vehicle…</option>
                            @foreach($vehicles as $v)<option value="{{ $v->id }}">{{ $v->registration_no }}</option>@endforeach</select>
                        <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Assign</button></form>
                    <form method="POST" action="{{ route('delivery.auto-assign', $o) }}">@csrf
                        <button class="px-3 py-1 bg-teal-600 text-white rounded text-sm">Auto-Assign Best Driver</button></form>
                    <form method="POST" action="{{ route('delivery.transition', $o) }}" class="flex gap-2 mt-3">@csrf
                        <select name="to" class="rounded border-gray-300 text-sm flex-1">
                            @foreach(\Modules\Delivery\Models\DeliveryOrder::TRANSITIONS[$o->status] ?? [] as $to)<option value="{{ $to }}">{{ str_replace('_',' ',$to) }}</option>@endforeach
                        </select><button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Move</button></form>
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Packages</h3>
                    @foreach($o->packages as $p)<div class="flex justify-between text-sm border-b py-1"><span class="font-mono">{{ $p->reference }}</span><span class="text-xs">{{ $p->status }}</span></div>@endforeach
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Failed / Reschedule / Return</h3>
                    <form method="POST" action="{{ route('delivery.fail', $o) }}" class="flex gap-2 mb-2">@csrf
                        <select name="reason" class="rounded border-gray-300 text-sm flex-1">@foreach(\Modules\Delivery\Models\DeliveryAttempt::REASONS as $r)<option value="{{ $r }}">{{ str_replace('_',' ',$r) }}</option>@endforeach</select>
                        <button class="px-3 py-1 bg-red-600 text-white rounded text-sm">Record Failure</button></form>
                    <form method="POST" action="{{ route('delivery.reschedule', $o) }}" class="flex gap-2 mb-2">@csrf
                        <input type="date" name="date" class="rounded border-gray-300 text-sm"><button class="px-3 py-1 bg-amber-600 text-white rounded text-sm">Reschedule</button></form>
                    <form method="POST" action="{{ route('delivery.return', $o) }}" class="flex gap-2">@csrf
                        <input name="reason" placeholder="Return reason" class="rounded border-gray-300 text-sm flex-1"><button class="px-3 py-1 bg-red-700 text-white rounded text-sm">Initiate Return</button></form>
                </div>
            </div>
            <div class="space-y-5">
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">POD &amp; COD</h3>
                    @if($o->pod)<div class="text-sm border rounded p-2 mb-2 bg-green-50">Delivered to <strong>{{ $o->pod->recipient_name }}</strong> at {{ $o->pod->delivered_at->format('d M H:i') }}</div>@endif
                    <form method="POST" action="{{ route('delivery.cod', $o) }}" class="flex gap-2">@csrf
                        <input name="amount" placeholder="COD amount" class="rounded border-gray-300 text-sm w-28">
                        <select name="method" class="rounded border-gray-300 text-sm"><option value="cash">cash</option><option value="mobile_money">mobile money</option><option value="card">card</option></select>
                        <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Record COD</button></form>
                    @foreach($o->cod as $c)
                    <div class="flex justify-between text-sm border-b py-1 mt-2"><span>{{ number_format($c->amount) }} {{ $c->currency }} ({{ $c->method }})</span>
                        @if($c->status !== 'reconciled')<form method="POST" action="{{ route('delivery.cod.reconcile', $c) }}" class="flex gap-1">@csrf
                            <input name="payment_ref" placeholder="Ref" class="rounded border-gray-300 text-xs w-24"><button class="px-2 py-0.5 bg-green-600 text-white rounded text-xs">Reconcile</button></form>
                        @else<span class="text-xs px-2 rounded bg-green-100 text-green-700">reconciled</span>@endif</div>
                    @endforeach
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Attempts &amp; Returns</h3>
                    @foreach($o->attempts as $a)<div class="text-sm border-b py-1">{{ str_replace('_',' ',$a->reason) }} — {{ $a->created_at->format('d M H:i') }}</div>@endforeach
                    @foreach($o->returns as $rt)
                    <div class="border rounded p-2 mt-2 text-sm flex justify-between"><span>{{ $rt->reference }} — {{ $rt->status }}</span>
                        @if($rt->status !== 'received_warehouse')<form method="POST" action="{{ route('delivery.returns.receive', $rt) }}">@csrf<button class="px-2 py-0.5 bg-indigo-600 text-white rounded text-xs">Receive</button></form>@endif</div>
                    @endforeach
                </div>
                <div class="bg-white rounded-lg shadow-sm p-5">
                    <h3 class="font-semibold mb-2">Timeline</h3>
                    <div class="space-y-1 max-h-56 overflow-auto">
                        @foreach($o->events as $e)<div class="text-xs flex gap-2"><span class="text-gray-400 shrink-0">{{ $e->created_at->format('d M H:i') }}</span><span>{{ $e->event }}</span><span class="text-gray-400">{{ $e->actor_type }}</span></div>@endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
