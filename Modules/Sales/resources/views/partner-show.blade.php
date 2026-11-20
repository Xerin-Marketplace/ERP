<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">{{ ucfirst($p->type) }} {{ $p->reference }}</h2></x-slot>
    <div class="grid md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg shadow p-4 text-sm space-y-1">
            <div><b>{{ $p->name }}</b> · {{ $p->status }}</div>
            <div>{{ $p->contact_name }} · {{ $p->phone }} · {{ $p->email }}</div>
            <div>Reg: {{ $p->registration_ref ?? '—' }}</div>
            <div class="mt-2 flex gap-1 flex-wrap">
                @foreach(['pending_verification','under_review','approved','active','suspended','rejected','terminated'] as $to)
                <form method="post" action="{{ route('sales.partners.transition',$p) }}">@csrf<input type="hidden" name="to" value="{{ $to }}"><button class="text-xs bg-gray-100 rounded px-2 py-1">{{ $to }}</button></form>
                @endforeach
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold text-sm mb-2">Performance (real transactions)</h3>
            <table class="text-sm w-full">@foreach($perf as $k=>$v)<tr class="border-t"><td class="py-1 text-gray-600">{{ str_replace('_',' ',$k) }}</td><td class="text-right font-mono">{{ is_numeric($v)?number_format($v,2):$v }}</td></tr>@endforeach</table>
        </div>
    </div>
</x-app-layout>
