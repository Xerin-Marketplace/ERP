<x-app-layout>
    <x-slot name="header">Last-Mile Delivery Operations</x-slot>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([['Pending Dispatch','pending_dispatch','amber'],['Unassigned','unassigned','red'],['Active Deliveries','active','indigo'],['Delivered','delivered','green'],['Failed','failed','red'],['Open Returns','returns','amber'],['COD Outstanding','cod_outstanding','amber'],['Drivers Online','drivers_online','teal']] as [$l,$k,$c])
        <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500 uppercase">{{ $l }}</p>
            <p class="text-2xl font-bold text-{{ $c }}-600">{{ $k === 'cod_outstanding' ? number_format($stats[$k] ?? 0) : ($stats[$k] ?? 0) }}</p></div>
        @endforeach
    </div>
</x-app-layout>
