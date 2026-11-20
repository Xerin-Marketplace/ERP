<x-app-layout>
    <x-slot name="header">Customer 360 — {{ $data['profile']->name }}</x-slot>
    <div class="grid md:grid-cols-2 gap-5">
        @foreach([['Quotations','quotations','reference'],['Packages','packages','reference'],['Deliveries','deliveries','reference'],['Invoices','invoices','reference'],['Tickets','tickets','ticket_number'],['Complaints','complaints','reference'],['Claims','claims','reference']] as [$label,$key,$ref])
        <div class="bg-white rounded-lg shadow-sm p-5"><h3 class="font-semibold mb-2">{{ $label }} ({{ $data[$key]->count() }})</h3>
            @foreach($data[$key] as $item)<div class="text-sm border-b py-1 font-mono">{{ $item->{$ref} }}</div>@endforeach
            @if($data[$key]->isEmpty())<p class="text-gray-400 text-sm">None.</p>@endif
        </div>
        @endforeach
    </div>
</x-app-layout>
