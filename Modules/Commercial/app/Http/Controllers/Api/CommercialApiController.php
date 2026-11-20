<?php

namespace Modules\Commercial\Http\Controllers\Api;

use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commercial\Models\{Customer, Lead, Inquiry, Quotation, RateCard};
use Modules\Commercial\Services\{CustomerService, LeadService, InquiryService, QuotationService, PricingService};

class CommercialApiController extends Controller
{
    // ── Customers ──────────────────────────────────────────
    public function customers(Request $r)
    {
        return ApiResponse::paginated(
            Customer::for($r->user())->when($r->q, fn ($q, $v) => $q->where('name', 'like', "%{$v}%"))
                ->when($r->status, fn ($q, $v) => $q->where('status', $v))->latest()->paginate(25)
        );
    }

    public function customerShow(Request $r, Customer $customer)
    {
        return ApiResponse::success($customer->load(['contacts','addresses','branch:id,name','inquiries:id,customer_id,reference,status','quotations:id,customer_id,number,status,total,currency']));
    }

    public function customerStore(Request $r, CustomerService $svc)
    {
        $data = $r->validate(['type' => 'required|in:' . implode(',', Customer::TYPES), 'name' => 'required|string|max:255',
            'email' => 'nullable|email', 'phone' => 'nullable|string|max:40', 'branch_id' => 'nullable|exists:branches,id',
            'country' => 'nullable|string|max:100', 'city' => 'nullable|string|max:100']);
        return ApiResponse::created($svc->create($data, $r->user()->id));
    }

    // ── Leads ──────────────────────────────────────────────
    public function leads(Request $r)
    {
        return ApiResponse::paginated(
            Lead::for($r->user())->when($r->stage, fn ($q, $v) => $q->where('stage', $v))->latest()->paginate(25)
        );
    }

    public function leadStore(Request $r, LeadService $svc)
    {
        $data = $r->validate(['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:40', 'email' => 'nullable|email',
            'source' => 'nullable|in:' . implode(',', Lead::SOURCES), 'branch_id' => 'nullable|exists:branches,id']);
        return ApiResponse::created($svc->create($data, $r->user()->id));
    }

    public function leadConvert(Request $r, Lead $lead, LeadService $svc)
    {
        abort_if($lead->converted_customer_id, 422, 'Already converted.');
        return ApiResponse::success($svc->convert($lead, $r->user()->id, $r->input('customer_id')));
    }

    // ── Inquiries ──────────────────────────────────────────
    public function inquiries(Request $r)
    {
        return ApiResponse::paginated(
            Inquiry::for($r->user())->with('customer:id,name')->when($r->status, fn ($q, $v) => $q->where('status', $v))->latest()->paginate(25)
        );
    }

    public function inquiryStore(Request $r, InquiryService $svc)
    {
        $data = $r->validate(['customer_id' => 'nullable|exists:customers,id', 'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:40', 'origin_country' => 'nullable|string|max:100',
            'destination_country' => 'nullable|string|max:100', 'cargo_category' => 'nullable|in:' . implode(',', Inquiry::CATEGORIES),
            'cargo_description' => 'nullable|string', 'estimated_weight' => 'nullable|numeric|min:0',
            'package_count' => 'nullable|integer|min:0', 'source' => 'required|in:' . implode(',', Inquiry::SOURCES)]);
        return ApiResponse::created($svc->create($data, $r->user()->id));
    }

    // ── Quotations ─────────────────────────────────────────
    public function quotations(Request $r)
    {
        return ApiResponse::paginated(
            Quotation::for($r->user())->with('customer:id,name')->when($r->status, fn ($q, $v) => $q->where('status', $v))->latest()->paginate(25)
        );
    }

    public function quotationShow(Request $r, Quotation $quotation)
    {
        return ApiResponse::success($quotation->load(['customer','items','events.user:id,name','handover']));
    }

    public function quotationStore(Request $r, QuotationService $svc)
    {
        $data = $r->validate(['customer_id' => 'required|exists:customers,id', 'inquiry_id' => 'nullable|exists:inquiries,id',
            'branch_id' => 'nullable|exists:branches,id', 'origin' => 'nullable|string|max:150',
            'destination' => 'nullable|string|max:150', 'cargo_description' => 'nullable|string',
            'gross_weight' => 'nullable|numeric|min:0', 'volumetric_weight' => 'nullable|numeric|min:0',
            'rate_card_id' => 'nullable|exists:rate_cards,id', 'valid_until' => 'nullable|date']);
        return ApiResponse::created($svc->create($data, $r->user()->id));
    }

    public function quotationAction(Request $r, Quotation $quotation, string $action, QuotationService $svc)
    {
        $map = ['submit' => 'pending_approval', 'approve' => 'approved', 'send' => 'sent'];
        abort_unless(isset($map[$action]), 404);
        if ($map[$action] === 'approved') abort_unless($r->user()->can('quotation.approve'), 403);
        return ApiResponse::success($svc->transition($quotation, $map[$action], $r->user()->id));
    }

    // ── Rate cards / pricing ───────────────────────────────
    public function rateCards(Request $r)
    {
        return ApiResponse::paginated(RateCard::latest()->paginate(25));
    }

    public function priceQuote(Request $r, PricingService $pricing)
    {
        $data = $r->validate(['gross_weight' => 'nullable|numeric|min:0', 'volumetric_weight' => 'nullable|numeric|min:0',
            'origin_country' => 'nullable|string', 'destination_country' => 'nullable|string',
            'service_type' => 'nullable|string', 'branch_id' => 'nullable|exists:branches,id']);
        $cw = $pricing->chargeableWeight($data['gross_weight'] ?? null, $data['volumetric_weight'] ?? null);
        $card = RateCard::match($data);
        return ApiResponse::success([
            'chargeable_weight' => $cw,
            'rate_card' => $card?->only(['id','code','name','currency','volumetric_divisor']),
            'breakdown' => $card ? $pricing->quote($card, $cw) : null,
        ]);
    }

    // ── Handover ───────────────────────────────────────────
    public function handovers(Request $r)
    {
        return ApiResponse::paginated(
            \Modules\Commercial\Models\CommercialHandover::with('quotation:id,number,total,currency')->latest()->paginate(25)
        );
    }
}
