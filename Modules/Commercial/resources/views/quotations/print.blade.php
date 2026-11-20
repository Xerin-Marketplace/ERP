<!DOCTYPE html><html><head><meta charset="utf-8"><title>{{ $quotation->number }}</title><style>
body{font-family:ui-sans-serif,system-ui,sans-serif;color:#111;max-width:800px;margin:2rem auto;padding:0 1rem}
h1{font-size:1.4rem;margin:0}.muted{color:#6b7280;font-size:.8rem}table{width:100%;border-collapse:collapse;margin-top:1rem}
th,td{padding:.5rem .75rem;text-align:left;border-bottom:1px solid #e5e7eb;font-size:.9rem}tfoot td{font-weight:700}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-top:1.5rem}.box{border:1px solid #e5e7eb;border-radius:.375rem;padding:1rem;font-size:.85rem}
@media print{.noprint{display:none}}
</style></head><body>
<button class="noprint" onclick="window.print()" style="float:right;padding:.5rem 1rem">Print / Save PDF</button>
<h1>QUOTATION</h1><div class="muted">{{ $quotation->number }} · v{{ $quotation->version }} · Issued {{ $quotation->created_at->format('d M Y') }}@if($quotation->valid_until) · Valid until {{ $quotation->valid_until->format('d M Y') }}@endif</div>
<div class="grid"><div class="box"><strong>Xerin Logistics</strong><br>{{ $quotation->branch?->name ?? 'Head Office' }}<br>{{ $quotation->branch?->country ?? '' }}</div>
<div class="box"><strong>Bill To</strong><br>{{ $quotation->customer?->name }}<br>{{ $quotation->customer?->email }}<br>{{ $quotation->customer?->phone }}</div></div>
<table><thead><tr><th>Description</th><th style="text-align:right">Amount</th></tr></thead>
<tbody>@foreach($quotation->items as $i)<tr><td>{{ $i->label }}</td><td style="text-align:right">{{ $i->kind==='discount'?'−':'' }}{{ number_format($i->amount,2) }} {{ $quotation->currency }}</td></tr>@endforeach</tbody>
<tfoot><tr><td>Total</td><td style="text-align:right">{{ number_format($quotation->total,2) }} {{ $quotation->currency }}</td></tr></tfoot></table>
@if($quotation->cargo_description)<div class="box" style="margin-top:1rem"><strong>Cargo:</strong> {{ $quotation->cargo_description }} · {{ $quotation->package_count }} pkg · {{ $quotation->chargeable_weight }} kg chargeable · {{ $quotation->origin }} → {{ $quotation->destination }}</div>@endif
@if($quotation->payment_terms || $quotation->service_terms)<div class="box" style="margin-top:1rem"><strong>Terms:</strong> {{ $quotation->payment_terms }} {{ $quotation->service_terms }}</div>@endif
<p class="muted">Prepared by {{ $quotation->preparer?->name }} · This quotation is subject to Xerin Logistics standard terms.</p>
</body></html>
