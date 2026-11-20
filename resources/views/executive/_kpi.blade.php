@php
    $d = $kpi ?? ['value' => 0, 'pct' => null, 'delta' => null];
    $v = (float) $d['value'];
    $a = abs($v);
    if (($percent ?? false)) { $short = number_format($v, 1) . '%'; }
    elseif ($a >= 1_000_000_000) { $short = number_format($v / 1e9, 1) . 'B'; }
    elseif ($a >= 1_000_000)     { $short = number_format($v / 1e6, 1) . 'M'; }
    elseif ($a >= 10_000)        { $short = number_format($v / 1e3, 1) . 'K'; }
    elseif (($money ?? false))   { $short = number_format($v, 0); }
    else                         { $short = number_format($v, $v == (int) $v ? 0 : 1); }
    $full = number_format($v, 2) . (($percent ?? false) ? '%' : '');
    $up = ($d['delta'] ?? 0) >= 0;
    $chipOk = ($invert ?? false) ? ! $up : $up;
@endphp
<a href="{{ $href ?? '#' }}" title="{{ $full }}"
   class="group relative block rounded border border-gray-200 bg-white p-3 shadow-sm transition hover:border-xerin-500 hover:shadow">
    <div class="absolute inset-y-0 left-0 w-0.5 bg-xerin-500 opacity-0 transition group-hover:opacity-100"></div>
    <p class="text-[10px] font-semibold uppercase tracking-wider text-gray-500">{{ $title }}</p>
    <div class="mt-1 flex items-end justify-between gap-2">
        <p class="text-xl font-bold tabular-nums {{ $color ?? 'text-gray-900' }}">{{ $short }}</p>
        @isset($spark)
            <canvas class="kpi-spark h-7 w-20" data-key="{{ $spark }}" data-color="{{ $sparkColor ?? '#F47721' }}"></canvas>
        @endisset
    </div>
    <div class="mt-1.5">
        @if (($d['pct'] ?? null) !== null)
            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold {{ $chipOk ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                {{ $up ? '▲' : '▼' }} {{ abs($d['pct']) }}%
            </span>
            <span class="ml-1 text-[10px] text-gray-400">vs prev</span>
        @else
            <span class="text-[10px] text-gray-300">—</span>
        @endif
    </div>
</a>
