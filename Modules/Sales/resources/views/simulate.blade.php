<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Pricing Simulation <span class="text-xs font-normal text-gray-500">read-only — creates nothing</span></h2></x-slot>
    <form method="get" class="bg-white rounded-lg shadow p-4 grid md:grid-cols-4 gap-2">
        <input name="origin" placeholder="Origin city" value="{{ request('origin') }}" class="border rounded px-2 py-1">
        <input name="destination" placeholder="Destination city" value="{{ request('destination') }}" class="border rounded px-2 py-1">
        <input name="service_type" placeholder="Service type" value="{{ request('service_type') }}" class="border rounded px-2 py-1">
        <input name="customer_category" placeholder="Customer category" value="{{ request('customer_category') }}" class="border rounded px-2 py-1">
        <input name="gross_weight" type="number" step="0.01" placeholder="Gross kg" value="{{ request('gross_weight') }}" class="border rounded px-2 py-1">
        <input name="volumetric_weight" type="number" step="0.01" placeholder="Vol kg" value="{{ request('volumetric_weight') }}" class="border rounded px-2 py-1">
        <select name="campaign_id" class="border rounded px-2 py-1"><option value="">No campaign</option>@foreach($campaigns as $c)<option value="{{ $c->id }}">{{ $c->reference }} {{ $c->name }}</option>@endforeach</select>
        <button name="simulate" value="1" class="bg-blue-600 text-white rounded px-3 py-1">Simulate</button>
    </form>
    @if($result)
    <div class="bg-white rounded-lg shadow p-4 mt-4 text-sm">
        @if(isset($result['error']))<div class="text-red-600">{{ $result['error'] }}</div>@else
        <div class="font-mono text-xs bg-gray-50 rounded p-2 mb-2">{{ $result['explanation'] }}</div>
        <table class="w-full">@foreach(['rate_card','chargeable_weight','base','price','discount','final','currency','campaign'] as $k)
            <tr class="border-t"><td class="py-1 text-gray-600">{{ $k }}</td><td class="text-right font-mono">{{ $result[$k] ?? '—' }}</td></tr>@endforeach</table>@endif
    </div>
    @endif
</x-app-layout>
