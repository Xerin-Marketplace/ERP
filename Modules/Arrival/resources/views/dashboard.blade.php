<x-app-layout>
    <x-slot name="header">Tanzania Arrival &amp; Customs Operations</x-slot>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([['Expected','expected','blue'],['In Transit','in_transit','indigo'],['Arrived','arrived','teal'],['Reconciling','reconciling','amber'],['Customs Pending','customs_pending','amber'],['Awaiting Collection','awaiting_collection','indigo'],['Open Discrepancies','open_discrepancies','red'],['Received','received','green']] as [$l,$k,$c])
        <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500 uppercase">{{ $l }}</p><p class="text-2xl font-bold text-{{ $c }}-600">{{ $stats[$k] ?? 0 }}</p></div>
        @endforeach
    </div>
</x-app-layout>
