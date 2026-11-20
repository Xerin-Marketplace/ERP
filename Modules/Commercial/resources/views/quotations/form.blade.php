<x-app-layout>
    <x-slot name="header">New Quotation</x-slot>
    <form method="POST" action="{{ route('commercial.quotations.store') }}" class="max-w-3xl bg-white rounded-md border border-gray-200 p-6 grid md:grid-cols-2 gap-4">
        @csrf
        @if($inquiry)<input type="hidden" name="inquiry_id" value="{{ $inquiry->id }}"><div class="md:col-span-2 text-sm text-gray-600">From inquiry <span class="font-mono">{{ $inquiry->reference }}</span></div>@endif
        <div><label class="text-xs font-medium text-gray-600">Customer *</label><select name="customer_id" required class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="">—</option>@foreach(\Modules\Commercial\Models\Customer::orderBy('name')->limit(300)->get() as $c)<option value="{{ $c->id }}" @selected($inquiry?->customer_id===$c->id)>{{ $c->name }} ({{ $c->code }})</option>@endforeach</select></div>
        <div><label class="text-xs font-medium text-gray-600">Rate card (auto)</label><select name="rate_card_id" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="">auto-match</option>@foreach(\Modules\Commercial\Models\RateCard::where('status','active')->get() as $r)<option value="{{ $r->id }}">{{ $r->code }} — {{ $r->name }}</option>@endforeach</select></div>
        <div><label class="text-xs font-medium text-gray-600">Origin</label><input name="origin" value="{{ $inquiry?->origin_country }}" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Destination</label><input name="destination" value="{{ $inquiry?->destination_country }}" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Gross weight (kg)</label><input type="number" step="0.01" name="gross_weight" value="{{ $inquiry?->estimated_weight }}" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Volumetric weight (kg)</label><input type="number" step="0.01" name="volumetric_weight" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Packages</label><input type="number" name="package_count" value="{{ $inquiry?->package_count }}" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Valid until</label><input type="date" name="valid_until" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Discount</label><input type="number" step="0.01" name="discount" min="0" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Branch</label><select name="branch_id" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="">—</option>@foreach(\Modules\Organization\Models\Branch::orderBy('name')->get() as $b)<option value="{{ $b->id }}" @selected($inquiry?->branch_id===$b->id)>{{ $b->name }}</option>@endforeach</select></div>
        <div class="md:col-span-2"><label class="text-xs font-medium text-gray-600">Cargo description</label><textarea name="cargo_description" rows="2" class="mt-1 w-full rounded-sm border-gray-200 text-sm">{{ $inquiry?->cargo_description }}</textarea></div>
        <div class="md:col-span-2"><label class="text-xs font-medium text-gray-600">Payment terms</label><input name="payment_terms" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div class="md:col-span-2 flex gap-3"><button class="px-5 py-2 bg-xerin-700 text-white rounded-sm text-sm font-medium">Create Quotation</button><a href="{{ route('commercial.quotations.index') }}" class="px-5 py-2 border border-gray-200 rounded-sm text-sm">Cancel</a></div>
    </form>
</x-app-layout>
