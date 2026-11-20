<!DOCTYPE html><html><head><meta charset="utf-8"><title>Label {{ $package->reference }}</title><style>
body{font-family:ui-monospace,monospace;max-width:320px;margin:1rem auto;padding:1rem;border:2px solid #000}
h2{font-size:.95rem;margin:0 0 .25rem}.ref{font-size:1.05rem;font-weight:700;letter-spacing:.05rem}
.meta{font-size:.7rem;color:#333;margin-top:.5rem;line-height:1.5}.bars{display:flex;align-items:flex-end;height:56px;margin-top:.6rem}.b{background:#000}
@media print{.noprint{display:none}}
</style></head><body>
<button class="noprint" onclick="window.print()" style="float:right">Print</button>
<h2>XERIN LOGISTICS</h2>
<div class="ref">{{ $package->reference }}</div>
@php
// Code-128B barcode → SVG bars (start B + chars + checksum + stop)
$CODE128 = [32,33,34,35,36,37,38,39,40,41,42,43,44,45,46,47,48,49,50,51,52,53,54,55,56,57,58,59,60,61,62,63,64,65,66,67,68,69,70,71,72,73,74,75,76,77,78,79,80,81,82,83,84,85,86,87,88,89,90,91,92,93,94,95];
$PAT = ['212222','222122','222221','121223','121322','131222','122213','122312','132212','221213','221312','231212','112232','122132','122231','113222','123122','123221','223211','221132','221231','213212','223112','312131','311222','321122','321221','312212','322112','322211','212123','212321','232121','111323','131123','131321','112313','132113','132311','211313','231113','231311','112133','112331','132131','113123','113321','133121','313121','211331','231131','213113','213311','213131','311123','311321','331121','312113','312311','332111','314111','221411','431111','111224','111422','121124','121421','141122','141221','112214','112412','122114','122411','142112','142211','241211','221114','413111','241112','134111','111242','121142','121241','114212','124112','124211','411212','421112','421211','212141','214121','412121','111143','111341','131141','114113','114311','411113','411311','113141','114131','311141','411131','211412','211214','211232','2331112'];
$str = $package->reference; $codes = [104]; // Start B
foreach (str_split($str) as $ch) { $codes[] = ord($ch) - 32; }
$chk = 104; foreach ($codes as $i => $c) { if ($i) $chk += $c * $i; } $codes[] = $chk % 103; $codes[] = 106;
$bars = []; foreach ($codes as $c) { $p = $PAT[$c]; for ($i = 0; $i < strlen($p); $i++) $bars[] = [$p[$i], $i % 2 === 0]; }
@endphp
<div class="bars">@foreach($bars as [$w,$isBar])<div class="{{ $isBar ? 'b' : '' }}" style="width:{{ $w*2 }}px;height:100%"></div>@endforeach</div>
<div class="meta">{{ $package->customer?->code ?? 'UNIDENTIFIED' }} · Pkg {{ $package->sequence }} · {{ $package->branch?->code }}<br>{{ $package->handling && $package->handling!=='normal' ? '⚠ '.strtoupper($package->handling) : '' }}</div>
</body></html>
