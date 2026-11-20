<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Quotation {{ $quotation->number }}</title>
<style>body{font-family:ui-sans-serif,system-ui,sans-serif;background:#f3f4f6;margin:0;padding:2rem 1rem}.card{max-width:640px;margin:0 auto;background:#fff;border:1px solid #e5e7eb;border-radius:.5rem;padding:1.5rem}
h1{font-size:1.25rem;margin:0}.muted{color:#6b7280;font-size:.8rem}table{width:100%;border-collapse:collapse;margin:1rem 0}td{padding:.4rem 0;border-bottom:1px solid #f3f4f6;font-size:.9rem}
.btn{display:inline-block;padding:.7rem 1.5rem;border-radius:.375rem;border:0;font-size:.9rem;cursor:pointer;margin-right:.5rem}.yes{background:#047857;color:#fff}.no{background:#fff;border:1px solid #d1d5db}
.flash{padding:.75rem 1rem;border-radius:.375rem;margin-bottom:1rem;font-size:.85rem}.ok{background:#ecfdf5;color:#065f46}.err{background:#fef2f2;color:#991b1b}</style></head><body>
<div class="card">
    @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
    @if(session('err'))<div class="flash err">{{ session('err') }}</div>@endif
    <h1>Quotation {{ $quotation->number }}</h1>
    <div class="muted">Xerin Logistics · {{ $quotation->branch?->name }} · Valid until {{ $quotation->valid_until?->format('d M Y') ?? '—' }}</div>
    <table>@foreach($quotation->items as $i)<tr><td>{{ $i->label }}</td><td style="text-align:right">{{ number_format($i->amount,2) }} {{ $quotation->currency }}</td></tr>@endforeach
    <tr><td><strong>Total</strong></td><td style="text-align:right"><strong>{{ number_format($quotation->total,2) }} {{ $quotation->currency }}</strong></td></tr></table>
    <div class="muted">{{ $quotation->origin }} → {{ $quotation->destination }} · {{ $quotation->package_count }} pkg · {{ $quotation->chargeable_weight }} kg</div>
    @if($quotation->isOpen() && ! $quotation->isExpired())
        <form method="POST" style="margin-top:1.5rem">@csrf
            <button class="btn yes" name="action" value="accept" onclick="return confirm('Accept this quotation?')">Accept / Kukubali</button>
            <button class="btn no" name="action" value="reject" onclick="return confirm('Decline this quotation?')">Decline / Kataa</button>
        </form>
    @else
        <p class="muted" style="margin-top:1.5rem">This quotation is <strong>{{ $quotation->isExpired() ? 'expired' : $quotation->status }}</strong>.</p>
    @endif
    <p class="muted" style="margin-top:1.5rem">Questions? Contact your Xerin Logistics representative.</p>
</div></body></html>
