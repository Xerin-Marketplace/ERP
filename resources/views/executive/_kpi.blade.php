@php($d = $kpi ?? ['value' => 0, 'pct' => null])
<a href="{{ $href ?? '#' }}" class="block rounded-sm border border-gray-200 bg-white p-4 shadow-sm hover:border-xerin-400 transition">
    <p class="text-xs font-medium text-gray-500">{{ $title }}</p>
    <p class="mt-1 text-xl font-bold {{ $color ?? 'text-gray-900' }}">
        {{ $money ?? false ? number_format($d['value'], 2) : number_format($d['value']) }}
    </p>
    @if ($d['pct'] !== null)
        <p class="mt-1 text-xs {{ $d['delta'] >= 0 ? 'text-emerald-600' : 'text-red-600' }}">
            {{ $d['delta'] >= 0 ? '▲' : '▼' }} {{ abs($d['pct']) }}% vs previous period
        </p>
    @else
        <p class="mt-1 text-xs text-gray-400">—</p>
    @endif
</a>
