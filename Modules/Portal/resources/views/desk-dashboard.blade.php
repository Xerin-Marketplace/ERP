<x-app-layout>
    <x-slot name="header">Customer Service Desk</x-slot>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([['Open Tickets','open_tickets','amber'],['Open Complaints','open_complaints','red'],['Claims Investigating','claims_investigating','indigo'],['Pending Approval','claims_pending_approval','amber'],['Awaiting Settlement','claims_settlement','amber'],['Avg Rating','avg_rating','teal'],['Privacy Requests','privacy_open','indigo']] as [$l,$k,$c])
        <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500 uppercase">{{ $l }}</p><p class="text-2xl font-bold text-{{ $c }}-600">{{ $stats[$k] ?? 0 }}</p></div>
        @endforeach
    </div>
</x-app-layout>
