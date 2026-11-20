<x-app-layout>
    <x-slot name="header">New Inquiry</x-slot>
    <form method="POST" action="{{ route('commercial.inquiries.store') }}" class="max-w-3xl bg-white rounded-md border border-gray-200 p-6 grid md:grid-cols-2 gap-4">
        @csrf
        <div><label class="text-xs font-medium text-gray-600">Customer</label><select name="customer_id" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="">— walk-in / prospect —</option>@foreach(\Modules\Commercial\Models\Customer::orderBy('name')->limit(200)->get() as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>@endforeach</select></div>
        <div><label class="text-xs font-medium text-gray-600">Contact person</label><input name="contact_person" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Phone</label><input name="phone" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Email</label><input type="email" name="email" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Origin country</label><input name="origin_country" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Destination country</label><input name="destination_country" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Cargo category</label><select name="cargo_category" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="">—</option>@foreach(\Modules\Commercial\Models\Inquiry::CATEGORIES as $c)<option value="{{ $c }}">{{ ucfirst($c) }}</option>@endforeach</select></div>
        <div><label class="text-xs font-medium text-gray-600">Service type</label><input name="service_type" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Packages</label><input type="number" name="package_count" min="0" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Est. weight (kg)</label><input type="number" step="0.01" name="estimated_weight" min="0" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div><label class="text-xs font-medium text-gray-600">Source *</label><select name="source" required class="mt-1 w-full rounded-sm border-gray-200 text-sm">@foreach(\Modules\Commercial\Models\Inquiry::SOURCES as $s)<option value="{{ $s }}" @selected($s==='manual')>{{ ucfirst($s) }}</option>@endforeach</select></div>
        <div><label class="text-xs font-medium text-gray-600">Priority *</label><select name="priority" class="mt-1 w-full rounded-sm border-gray-200 text-sm"><option value="normal">Normal</option><option value="high">High</option><option value="low">Low</option></select></div>
        <div class="md:col-span-2"><label class="text-xs font-medium text-gray-600">Cargo description</label><textarea name="cargo_description" rows="2" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></textarea></div>
        <div class="md:col-span-2"><label class="text-xs font-medium text-gray-600">Special handling</label><input name="special_handling" class="mt-1 w-full rounded-sm border-gray-200 text-sm"></div>
        <div class="md:col-span-2 flex gap-3"><button class="px-5 py-2 bg-xerin-700 text-white rounded-sm text-sm font-medium">Create Inquiry</button><a href="{{ route('commercial.inquiries.index') }}" class="px-5 py-2 border border-gray-200 rounded-sm text-sm">Cancel</a></div>
    </form>
</x-app-layout>
