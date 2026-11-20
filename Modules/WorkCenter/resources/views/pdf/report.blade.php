<!DOCTYPE html>
<html><head><meta charset="utf-8"><style>
@page { margin: 110px 50px 70px 50px; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
header { position: fixed; top: -95px; left: 0; right: 0; height: 90px;
         border-bottom: 3px solid #1e40af; padding-bottom: 8px; }
header .brand { font-size: 20px; font-weight: bold; color: #1e40af; }
header .meta { float: right; text-align: right; font-size: 9px; color: #6b7280; }
footer { position: fixed; bottom: -55px; left: 0; right: 0; height: 45px;
         border-top: 1px solid #d1d5db; font-size: 8px; color: #6b7280; }
footer .page:after { content: "Page " counter(page) " of " counter(pages); }
.classification { text-transform: uppercase; letter-spacing: 2px; color: #991b1b; font-weight: bold; }
h1 { font-size: 18px; color: #111827; border-bottom: 1px solid #e5e7eb; padding-bottom: 6px; }
h2 { font-size: 13px; color: #1e40af; margin: 18px 0 6px; }
table { width: 100%; border-collapse: collapse; margin: 8px 0; }
td, th { border: 1px solid #d1d5db; padding: 5px 7px; text-align: left; font-size: 10px; }
th { background: #eff6ff; color: #1e3a8a; }
.cover { text-align: center; margin-top: 180px; page-break-after: always; }
.cover .title { font-size: 26px; font-weight: bold; color: #1e40af; }
.sig { margin-top: 40px; } .sig td { border: none; border-top: 1px solid #9ca3af; padding-top: 4px; width: 33%; }
.section-body { text-align: justify; }
.watermark { position: fixed; top: 40%; left: 15%; font-size: 60px; color: rgba(30,64,175,0.06);
             transform: rotate(-35deg); z-index: -1; }
</style></head><body>
<header>
  <div class="meta">{{ $report->ref }}<br>{{ $report->category->name ?? '' }}<br>
    {{ $report->period_start }} – {{ $report->period_end }}<br>
    <span class="classification">{{ $report->classification }}</span></div>
  <div class="brand">{{ $company?->name ?? 'XERIN GROUP' }}</div>
  <div style="font-size:9px;color:#6b7280">{{ $branch?->name ?? '' }} {{ $branch?->name ? '·' : '' }}
    Report v{{ $version->version }} · Generated {{ now()->format('d M Y H:i') }}</div>
</header>
<footer><span>{{ $company?->name ?? 'XERIN GROUP' }} · {{ $report->ref }} · {{ $report->classification }}</span>
  <span class="page" style="float:right"></span></footer>
@if($report->classification !== 'public')<div class="watermark">{{ strtoupper($report->classification) }}</div>@endif

<div class="cover">
  <div class="brand" style="font-size:32px">{{ $company?->name ?? 'XERIN GROUP' }}</div>
  <div class="title" style="margin-top:60px">{{ $report->title }}</div>
  <p style="margin-top:30px">{{ $report->category->name ?? '' }}</p>
  <p>Period: {{ $report->period_start }} – {{ $report->period_end }}</p>
  <p>Branch: {{ $branch?->name ?? '—' }}</p>
  <p>Prepared by: {{ $report->owner->name ?? '' }}</p>
  <p style="margin-top:40px;font-size:9px">Ref: {{ $report->ref }} · Version {{ $version->version }}</p>
</div>

<h1>{{ $report->title }}</h1>
@if($version->content)
  @foreach(($report->template?->sections ?? collect($version->content)->keys()->map(fn($k)=>['key'=>$k,'title'=>Str::headline($k)])) as $sec)
    @php $key = is_array($sec) ? $sec['key'] : $sec->key; $title = is_array($sec) ? ($sec['title'] ?? $key) : $sec->title; @endphp
    <h2>{{ $title }}</h2>
    <div class="section-body">{!! $version->content[$key] ?? '<em>—</em>' !!}</div>
  @endforeach
@else
  <p><em>Uploaded document — see attached file version {{ $version->version }}.</em></p>
@endif

<table class="sig"><tr>
  <td>Prepared by<br>{{ $report->owner->name ?? '' }}</td>
  <td>Reviewed by<br>{{ $report->reviews->last()?->reviewer_id ? \App\Models\User::find($report->reviews->last()->reviewer_id)?->name : '—' }}</td>
  <td>Approved · {{ $report->reviews->where('decision','approved')->last()?->decided_at?->format('d M Y') ?? '—' }}</td>
</tr></table>
</body></html>
