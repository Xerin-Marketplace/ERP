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
    $goodWhenDown = $invert ?? false;
    $chipOk = $goodWhenDown ? ! $up : $up;
    $solid = $bg ?? null; // e.g. 'from-emerald-500 to-emerald-700'
@endphp
<a href="{{ $href ?? '#' }}" title="{{ $full }}"
   class="group relative block overflow-hidden rounded-xl p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg
    {{ $solid ? 'bg-gradient-to-br ' . $solid . ' text-white border border-transparent' : 'bg-white text-gray-900 border border-gray-200 hover:border-xerin-400' }}">
    @unless($solid)
        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r {{ $accent ?? 'from-xerin-500 to-xerin-700' }}"></div>
    @endunless
    <p class="text-[11px] font-semibold uppercase tracking-wider {{ $solid ? 'text-white/80' : 'text-gray-500' }}">{{ $title }}</p>
    <div class="mt-1 flex items-end justify-between gap-2">
        <p class="text-2xl font-bold tabular-nums {{ $solid ? 'text-white' : ($color ?? 'text-gray-900') }}">{{ $short }}</p>
        @isset($spark)
            <canvas class="kpi-spark h-9 w-24" data-key="{{ $spark }}" data-color="{{ $solid ? '#ffffff' : ($sparkColor ?? '#0369a1') }}"></canvas>
        @endisset
    </div>
    <div class="mt-2">
        @if (($d['pct'] ?? null) !== null)
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold
                {{ $solid
                    ? 'bg-white/20 text-white'
                    : ($chipOk ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700') }}">
                {{ $up ? '▲' : '▼' }} {{ abs($d['pct']) }}%
            </span>
            <span class="ml-1 text-[11px] {{ $solid ? 'text-white/60' : 'text-gray-400' }}">vs prev</span>
        @else
            <span class="text-[11px] {{ $solid ? 'text-white/40' : 'text-gray-300' }}">—</span>
        @endif
    </div>
</a>
