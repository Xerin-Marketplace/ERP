<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold">Commercial Dashboard</h2></x-slot>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach([['Customers',$d['customers']],['Active Leads',$d['leads_active']],['Open Opps',$d['opportunities']],['Won',$d['opportunities_won']],['Contracts',$d['contracts_active']],['Expiring ≤30d',$d['contracts_expiring']],['Active Partners',$d['partners_active']]] as [$l,$v])
        <div class="bg-white rounded-lg shadow p-4"><div class="text-xs text-gray-500">{{ $l }}</div><div class="text-2xl font-bold">{{ $v }}</div></div>
        @endforeach
    </div>
    <div class="grid md:grid-cols-2 gap-4 mt-4">
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-2">Revenue (Finance source-of-truth)</h3>
            <table class="w-full text-sm">@foreach([['Pipeline (open opps)',$analytics['pipeline_value']],['Weighted pipeline',$analytics['weighted_pipeline']],['Quoted',$analytics['quoted_value']],['Invoiced',$analytics['invoiced']],['Collected',$analytics['collected']],['Outstanding',$analytics['outstanding']],['Commission cost',$analytics['commission_cost']]] as [$l,$v])
                <tr class="border-t"><td class="py-1 text-gray-600">{{ $l }}</td><td class="text-right font-mono">{{ number_format($v,2) }}</td></tr>@endforeach
            </table>
        </div>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-semibold mb-2">Forecast <span class="text-xs font-normal text-amber-600">estimate — not accounting revenue</span></h3>
            <table class="w-full text-sm">@forelse($forecast as $f)<tr class="border-t"><td class="py-1">{{ $f['month'] }}</td><td class="text-right font-mono">{{ number_format($f['weighted'],2) }}</td></tr>@empty<tr><td class="py-2 text-gray-400">No open opportunities with close dates</td></tr>@endforelse</table>
            <div class="mt-3 pt-3 border-t text-sm">Pending commissions: <b>{{ number_format($d['commissions_pending'],2) }}</b></div>
        </div>
    </div>
</x-app-layout>
