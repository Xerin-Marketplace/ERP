<x-app-layout>
    <x-slot name="header">{{ __('Finance Settings') }}</x-slot>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold mb-2">Company Configuration</h3>
            @if ($setting)
            <dl class="text-sm space-y-1">
                <div class="flex justify-between"><dt class="text-gray-500">Base currency</dt><dd class="font-mono">{{ $setting->base_currency }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Decimal places</dt><dd>{{ $setting->decimal_places }}</dd></div>
                <div class="flex justify-between"><dt class="text-gray-500">Journal approval required</dt><dd>{{ $setting->require_journal_approval ? 'Yes' : 'No' }}</dd></div>
            </dl>
            @else <p class="text-sm text-gray-400">Not configured.</p> @endif
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm">
            <h3 class="font-semibold mb-2">Financial Years &amp; Periods</h3>
            @foreach ($years as $y)
                <p class="text-sm font-semibold">{{ $y->name }} <span class="text-xs text-gray-400">{{ $y->starts_on->format('M Y') }}–{{ $y->ends_on->format('M Y') }} · {{ $y->status }}</span></p>
                <div class="flex flex-wrap gap-1 mt-1 mb-2">
                    @foreach ($y->periods as $p)<span class="rounded px-2 py-0.5 text-xs {{ $p->status==='open' ? 'bg-green-100 text-green-700' : 'bg-gray-200' }}">{{ $p->name }}</span>@endforeach
                </div>
            @endforeach
        </div>
        <div class="rounded-sm border border-gray-200 bg-white p-4 shadow-sm md:col-span-2">
            <h3 class="font-semibold mb-2">Account Mappings</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-xs text-gray-500"><tr><th>Key</th><th>Account</th></tr></thead>
                <tbody>
                @foreach ($mappings as $m)
                    @php $acc = $accounts->firstWhere('id', $m->account_id); @endphp
                    <tr class="border-t"><td class="py-1 font-mono text-xs">{{ $m->key }}</td><td class="py-1">{{ $acc?->code }} — {{ $acc?->name }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
