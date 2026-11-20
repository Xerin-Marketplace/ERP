<x-app-layout>
    <x-slot name="header">Package {{ $package->reference }}</x-slot>
    <div class="space-y-6" x-data="{tab:'ops'}">
        <div class="bg-white rounded-md border border-gray-200 p-5 flex flex-wrap justify-between gap-4">
            <div>
                <div class="flex items-center gap-3"><h2 class="font-mono">{{ $package->reference }}</h2><span class="px-2 py-0.5 rounded-full text-xs bg-gray-100 capitalize">{{ str_replace('_',' ',$package->status) }}</span>@if($package->unidentified)<span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-800">UNIDENTIFIED</span>@endif</div>
                <div class="mt-1 text-sm text-gray-600">{{ $package->customer?->name ?? '—' }} · {{ $package->branch?->name }} · seq {{ $package->sequence }}</div>
                <div class="mt-1 text-sm">{{ $package->gross_weight ?? '—' }} kg gross · {{ $package->volumetric_weight ?? '—' }} kg vol · <strong>{{ $package->chargeable_weight ?? '—' }} kg chargeable</strong> (÷{{ $package->volumetric_divisor ?? '—' }})</div>
            </div>
            @can('cargo.packages.print_labels')<a href="{{ route('cargo.packages.label',$package) }}" target="_blank" class="px-4 py-2 border border-gray-200 rounded-sm text-sm h-fit">Print Label</a>@endcan
        </div>
        @if($package->unidentified)
        <div class="bg-amber-50 border border-amber-200 rounded-md p-4">
            <strong class="text-sm text-amber-800">Unidentified cargo — verify ownership before linking.</strong>
            @can('cargo.receiving.manage_discrepancies')
            <form method="POST" action="{{ route('cargo.packages.identify',$package) }}" class="mt-2 flex gap-2">@csrf
                <select name="customer_id" required class="flex-1 rounded-sm border-gray-200 text-sm">@foreach(\Modules\Commercial\Models\Customer::orderBy('name')->limit(200)->get() as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>@endforeach</select>
                <button class="px-4 py-2 bg-amber-700 text-white rounded-sm text-sm">Link Customer</button></form>
            @endcan
        </div>
        @endif
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                @can('cargo.weighing.create')
                <form method="POST" action="{{ route('cargo.packages.measure',$package) }}" class="bg-white rounded-md border border-gray-200 p-5 grid grid-cols-3 gap-3">
                    @csrf<input type="hidden" name="kind" value="{{ $package->measurements->isEmpty() ? 'initial' : 'reweigh' }}">
                    <div class="col-span-3 font-semibold text-sm">{{ $package->measurements->isEmpty() ? 'Weigh & Measure' : 'Reweigh (history preserved)' }}</div>
                    <div><label class="text-xs text-gray-600">Gross kg</label><input type="number" step="0.01" name="gross_weight" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
                    <div><label class="text-xs text-gray-600">L cm</label><input type="number" step="0.01" name="length" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
                    <div><label class="text-xs text-gray-600">W cm</label><input type="number" step="0.01" name="width" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
                    <div><label class="text-xs text-gray-600">H cm</label><input type="number" step="0.01" name="height" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
                    <div><label class="text-xs text-gray-600">Divisor</label><input type="number" name="divisor" value="6000" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
                    <div class="self-end"><button class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm">Record</button></div>
                </form>
                @endcan
                @can('cargo.inspection.perform')
                <form method="POST" action="{{ route('cargo.packages.inspect',$package) }}" class="bg-white rounded-md border border-gray-200 p-5 space-y-3">
                    @csrf<div class="font-semibold text-sm">Inspection</div>
                    <div class="grid grid-cols-3 gap-2 text-xs">@foreach(['condition_ok'=>'Condition OK','integrity_ok'=>'Integrity OK','packaging_ok'=>'Packaging OK','fragile'=>'Fragile','liquid'=>'Liquid','battery'=>'Battery','perishable'=>'Perishable','dangerous'=>'Dangerous','docs_ok'=>'Docs OK'] as $k=>$l)
                        <label class="flex items-center gap-1.5"><input type="checkbox" name="checklist[{{ $k }}]" value="1" class="rounded border-gray-300">{{ $l }}</label>@endforeach</div>
                    <select name="outcome" required class="w-full rounded-sm border-gray-200 text-sm"><option value="">— outcome —</option>@foreach(\Modules\Cargo\Models\Inspection::OUTCOMES as $o)<option value="{{ $o }}">{{ ucfirst(str_replace('_',' ',$o)) }}</option>@endforeach</select>
                    <input name="comments" placeholder="Comments" class="w-full rounded-sm border-gray-200 text-sm">
                    <button class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm">Submit Inspection</button>
                </form>
                @endcan
            </div>
            <div class="space-y-6">
                @can('warehouse.movements.create')
                <form method="POST" action="{{ route('cargo.packages.move',$package) }}" class="bg-white rounded-md border border-gray-200 p-5 space-y-3">
                    @csrf<div class="font-semibold text-sm">Move Package <span class="text-gray-500 font-normal">(now: {{ $package->location?->code ?? 'floor' }})</span></div>
                    <select name="location_id" required class="w-full rounded-sm border-gray-200 text-sm"><option value="">— destination —</option>@foreach($locations as $l)<option value="{{ $l->id }}">{{ $l->warehouse->code }} · {{ $l->code }} ({{ $l->type }})</option>@endforeach</select>
                    <select name="reason" class="w-full rounded-sm border-gray-200 text-sm"><option value="transfer">Transfer</option><option value="inspection_to_storage">Store</option><option value="quarantine">Quarantine</option><option value="hold">Hold</option><option value="pick_to_dispatch">Pick to dispatch</option></select>
                    <button class="px-4 py-2 bg-xerin-700 text-white rounded-sm text-sm">Move</button>
                </form>
                @endcan
                @if($package->onHold() && auth()->user()->can('cargo.inspection.approve'))
                <form method="POST" action="{{ route('cargo.packages.release',$package) }}" class="bg-red-50 border border-red-200 rounded-md p-4 flex items-center justify-between">@csrf
                    <span class="text-sm text-red-700 font-medium">Package on hold</span><button class="px-4 py-2 bg-red-700 text-white rounded-sm text-sm">Release Hold</button></form>
                @endif
                <div class="bg-white rounded-md border border-gray-200">
                    <div class="px-5 py-3 border-b border-gray-100 font-semibold text-sm">Timeline</div>
                    <div class="divide-y divide-gray-100 max-h-80 overflow-y-auto">
                        @forelse($package->events as $e)
                            <div class="px-5 py-2 text-xs flex justify-between"><span class="capitalize font-medium">{{ str_replace('_',' ',$e->event) }}</span><span class="text-gray-400">{{ $e->user?->name ?? 'system' }} · {{ $e->created_at->format('d M H:i') }}</span></div>
                        @empty <p class="px-5 py-4 text-sm text-gray-400">No events.</p> @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
