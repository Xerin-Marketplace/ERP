<x-app-layout>
    <x-slot name="header">New Delivery Order</x-slot>
    <div class="bg-white rounded-lg shadow-sm p-6 max-w-2xl">
        <form method="POST" action="{{ route('delivery.store') }}" class="space-y-4">@csrf
            <div class="grid grid-cols-2 gap-4">
                <div><label class="text-xs text-gray-500">Customer</label>
                    <select name="customer_id" required class="w-full rounded border-gray-300">@foreach($customers as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                <div><label class="text-xs text-gray-500">Branch</label>
                    <select name="branch_id" required class="w-full rounded border-gray-300">@foreach(\Modules\Organization\Models\Branch::all() as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></div>
                <div><label class="text-xs text-gray-500">Service</label>
                    <select name="service_id" class="w-full rounded border-gray-300"><option value="">—</option>@foreach($services as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                <div><label class="text-xs text-gray-500">Zone</label>
                    <select name="zone_id" class="w-full rounded border-gray-300"><option value="">—</option>@foreach($zones as $z)<option value="{{ $z->id }}">{{ $z->name }} ({{ number_format($z->fee) }})</option>@endforeach</select></div>
                <div><label class="text-xs text-gray-500">Type</label>
                    <select name="delivery_type" class="w-full rounded border-gray-300"><option value="delivery">Delivery</option><option value="collection">Customer Collection</option></select></div>
                <div><label class="text-xs text-gray-500">Scheduled Date</label><input type="date" name="scheduled_date" class="w-full rounded border-gray-300"></div>
                <div><label class="text-xs text-gray-500">COD Amount</label><input name="cod_amount" class="w-full rounded border-gray-300"></div>
                <div><label class="text-xs text-gray-500">Package IDs</label><input name="package_ids[]" required placeholder="comma-sep" class="w-full rounded border-gray-300"
                    onchange="this.value.split(',').forEach((v,i)=>{if(i>0){const n=this.cloneNode();n.value=v.trim();this.parentNode.insertBefore(n,this)}this.value=this.value.split(',')[0].trim()})"></div>
            </div>
            <textarea name="instructions" placeholder="Delivery instructions" class="w-full rounded border-gray-300 text-sm"></textarea>
            <button class="px-5 py-2 bg-indigo-600 text-white rounded">Create Order</button>
        </form>
    </div>
</x-app-layout>
