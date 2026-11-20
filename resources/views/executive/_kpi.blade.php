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
    $full = number_format($v, 2) . ($percent ?? false ? '%' : '');
    $up = ($d['delta'] ?? 0) >= 0;
    $goodWhenDown = $invert ?? false;
    $chipOk = $goodWhenDown ? ! $up : $up;
@endphp
<a href="{{ $href ?? '#' }}" title="{{ $full }}"
   class="group relative block overflow-hidden rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md hover:border-xerin-400">
    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $accent ?? 'from-xerin-500 to-xerin-700' }}"></div>
    <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500">{{ $title }}</p>
    <div class="mt-1 flex items-end justify-between gap-2">
        <p class="text-2xl font-bold tabular-nums {{ $color ?? 'text-gray-900' }}">{{ $short }}</p>
        @isset($spark)
            <canvas class="kpi-spark h-9 w-24" data-key="{{ $spark }}" data-color="{{ $sparkColor ?? '#0369a1' }}"></canvas>
        @endisset
    </div>
    <div class="mt-2">
        @if (($d['pct'] ?? null) !== null)
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $chipOk ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                {{ $up ? '▲' : '▼' }} {{ abs($d['pct']) }}%
            </span>
            <span class="ml-1 text-[11px] text-gray-400">vs prev</span>
        @else
            <span class="text-[11px] text-gray-300">—</span>
        @endif
    </div>
</a>
