<x-app-layout>
    <x-slot name="header">Shipment &amp; Airline Operations</x-slot>
    <div class="space-y-6">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([['Active Shipments','active','blue'],['Awaiting Booking','awaiting_booking','amber'],['In Transit','in_transit','indigo'],['Handed Over','handed_over','teal'],['Arrived','arrived','green'],['Delayed','delayed','red'],['Open Exceptions','open_exceptions','red'],['Total','by_status','gray']] as [$label,$key,$color])
            <div class="bg-white rounded-lg shadow-sm p-4">
                <p class="text-xs text-gray-500 uppercase">{{ $label }}</p>
                <p class="text-2xl font-bold text-{{ $color }}-600">{{ $key === 'by_status' ? array_sum($stats['by_status']) : ($stats[$key] ?? 0) }}</p>
            </div>
            @endforeach
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-3">By Status</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($stats['by_status'] as $status => $n)
                <a href="{{ route('shipment.index', ['status' => $status]) }}" class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-sm hover:bg-indigo-100">{{ str_replace('_',' ',$status) }} ({{ $n }})</a>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
