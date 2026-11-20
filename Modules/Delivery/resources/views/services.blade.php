<x-app-layout>
    <x-slot name="header">Delivery Services &amp; Zones</x-slot>
    <div class="grid md:grid-cols-2 gap-5">
        <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-3">Services</h3>
            <form method="POST" action="{{ route('delivery.services.store') }}" class="flex gap-2 mb-3">@csrf
                <input name="code" required placeholder="Code" class="rounded border-gray-300 text-sm w-20">
                <input name="name" required placeholder="Name" class="rounded border-gray-300 text-sm flex-1">
                <select name="pricing_method" class="rounded border-gray-300 text-sm"><option value="zone_fixed">zone</option><option value="weight">weight</option><option value="hybrid">hybrid</option></select>
                <input name="base_fee" placeholder="Fee" class="rounded border-gray-300 text-sm w-20"><button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Add</button></form>
            <table class="w-full text-sm"><tbody class="divide-y">@foreach($services as $s)
                <tr><td class="py-2 font-medium">{{ $s->name }} <span class="text-xs text-gray-400">({{ $s->code }})</span></td><td>{{ $s->pricing_method }}</td><td class="text-right">{{ number_format($s->base_fee) }}</td></tr>@endforeach</tbody></table></div>
        <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-3">Zones</h3>
            <form method="POST" action="{{ route('delivery.zones.store') }}" class="flex gap-2 mb-3">@csrf
                <input name="name" required placeholder="Zone" class="rounded border-gray-300 text-sm flex-1">
                <input name="district" placeholder="District" class="rounded border-gray-300 text-sm w-28">
                <input name="fee" placeholder="Fee" class="rounded border-gray-300 text-sm w-20"><button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Add</button></form>
            <table class="w-full text-sm"><tbody class="divide-y">@foreach($zones as $z)
                <tr><td class="py-2">{{ $z->name }}</td><td class="text-xs text-gray-500">{{ $z->district }}</td><td class="text-right">{{ number_format($z->fee) }}</td></tr>@endforeach</tbody></table></div>
    </div>
</x-app-layout>
