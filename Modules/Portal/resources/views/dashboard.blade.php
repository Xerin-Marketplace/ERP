<x-app-layout>
    <x-slot name="header">My Account — {{ $c->name }}</x-slot>
    <div class="space-y-5">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([['Total Packages','packages_total','indigo'],['In Transit','packages_in_transit','blue'],['In Warehouse','packages_in_warehouse','amber'],['Delivered','packages_delivered','green'],['Active Deliveries','active_deliveries','indigo'],['Open Tickets','open_tickets','amber'],['Pending Claims','pending_claims','red'],['Balance Due','outstanding_balance','red']] as [$l,$k,$c])
            <div class="bg-white rounded-lg shadow-sm p-4"><p class="text-xs text-gray-500 uppercase">{{ $l }}</p>
                <p class="text-2xl font-bold text-{{ $c }}-600">{{ $k === 'outstanding_balance' ? number_format($stats[$k] ?? 0) : ($stats[$k] ?? 0) }}</p></div>
            @endforeach
        </div>
        <div class="flex gap-3 flex-wrap">
            <a href="{{ route('portal.tracking') }}" class="px-4 py-2 bg-indigo-600 text-white rounded text-sm">Track Cargo</a>
            <a href="{{ route('portal.tickets') }}" class="px-4 py-2 bg-white border rounded text-sm">Support Tickets</a>
            <a href="{{ route('portal.claims') }}" class="px-4 py-2 bg-white border rounded text-sm">Claims</a>
            <a href="{{ route('portal.statement') }}" class="px-4 py-2 bg-white border rounded text-sm">Statement</a>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-5">
            <h3 class="font-semibold mb-2">Quick Feedback</h3>
            <form method="POST" action="{{ route('portal.feedback') }}" class="flex gap-2">@csrf
                <input name="survey_key" placeholder="e.g. delivery:123" class="rounded border-gray-300 text-sm w-36">
                <select name="rating" class="rounded border-gray-300 text-sm"><option value="5">5 ★</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option></select>
                <input name="comment" placeholder="Comment" class="rounded border-gray-300 text-sm flex-1">
                <button class="px-3 py-1 bg-indigo-600 text-white rounded text-sm">Send</button></form>
        </div>
    </div>
</x-app-layout>
