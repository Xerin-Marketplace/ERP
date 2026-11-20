<?php

namespace Modules\Commercial\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Commercial\Models\{Customer, Lead, Inquiry, Quotation, RateCard};
use Modules\Commercial\Services\{CustomerService, LeadService, InquiryService, QuotationService};

class CommercialController extends Controller
{
    public function dashboard()
    {
        $u = auth()->user();
        return view('commercial::dashboard', [
            'stats' => [
                'customers' => Customer::for($u)->count(),
                'active_customers' => Customer::for($u)->where('status', 'active')->count(),
                'leads' => Lead::for($u)->whereNotIn('stage', ['won','lost','disqualified'])->count(),
                'open_inquiries' => Inquiry::for($u)->whereNotIn('status', ['closed','converted','accepted','rejected'])->count(),
                'quotes_sent' => Quotation::for($u)->whereIn('status', ['sent','viewed','negotiation'])->count(),
                'quotes_accepted' => Quotation::for($u)->where('status', 'accepted')->count(),
                'quoted_value' => Quotation::for($u)->whereNotIn('status', ['cancelled','superseded'])->sum('total'),
                'accepted_value' => Quotation::for($u)->where('status', 'accepted')->sum('total'),
                'leads_by_stage' => Lead::for($u)->selectRaw('stage, COUNT(*) n')->groupBy('stage')->pluck('n', 'stage')->all(),
                'leads_by_source' => Lead::for($u)->selectRaw('source, COUNT(*) n')->groupBy('source')->pluck('n', 'source')->all(),
            ],
            'recent_leads' => Lead::for($u)->latest()->limit(8)->get(),
            'recent_quotes' => Quotation::for($u)->with('customer:id,name')->latest()->limit(8)->get(),
        ]);
    }

    // ── Customers ──────────────────────────────────────────
    public function customers(Request $r)
    {
        $customers = Customer::for($r->user())->with('branch:id,name')
            ->when($r->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('code', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")->orWhere('phone', 'like', "%{$v}%")))
            ->when($r->type, fn ($q, $v) => $q->where('type', $v))
            ->when($r->status, fn ($q, $v) => $q->where('status', $v))
            ->latest()->paginate(25)->withQueryString();
        return view('commercial::customers.index', compact('customers'));
    }

    public function customerShow(Customer $customer)
    {
        $customer->load(['contacts','addresses','documents','branch:id,name','owner:id,name',
            'inquiries' => fn ($q) => $q->latest()->limit(15),
            'quotations' => fn ($q) => $q->latest()->limit(15)]);
        return view('commercial::customers.show', compact('customer'));
    }

    public function customerCreate() { return view('commercial::customers.form', ['customer' => new Customer]); }
    public function customerEdit(Customer $customer) { return view('commercial::customers.form', compact('customer')); }

    public function customerStore(Request $r, CustomerService $svc)
    {
        $data = $r->validate([
            'type' => 'required|in:' . implode(',', Customer::TYPES), 'name' => 'required|string|max:255',
            'trading_name' => 'nullable|string|max:255', 'email' => 'nullable|email', 'phone' => 'nullable|string|max:40',
            'whatsapp' => 'nullable|string|max:40', 'country' => 'nullable|string|max:100', 'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500', 'registration_no' => 'nullable|string|max:100', 'tin' => 'nullable|string|max:60',
            'contact_person' => 'nullable|string|max:255', 'source' => 'nullable|string|max:40',
            'branch_id' => 'nullable|exists:branches,id', 'status' => 'required|in:' . implode(',', Customer::STATUSES),
        ]);
        $svc->create($data, $r->user()->id);
        return redirect()->route('commercial.customers.index')->with('ok', 'Customer created.');
    }

    public function customerUpdate(Request $r, Customer $customer)
    {
        $customer->update($r->validate([
            'type' => 'required|in:' . implode(',', Customer::TYPES), 'name' => 'required|string|max:255',
            'trading_name' => 'nullable|string|max:255', 'email' => 'nullable|email', 'phone' => 'nullable|string|max:40',
            'whatsapp' => 'nullable|string|max:40', 'country' => 'nullable|string|max:100', 'city' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500', 'registration_no' => 'nullable|string|max:100', 'tin' => 'nullable|string|max:60',
            'contact_person' => 'nullable|string|max:255', 'source' => 'nullable|string|max:40',
            'branch_id' => 'nullable|exists:branches,id', 'status' => 'required|in:' . implode(',', Customer::STATUSES),
        ]));
        return redirect()->route('commercial.customers.show', $customer)->with('ok', 'Customer updated.');
    }

    // ── Leads ──────────────────────────────────────────────
    public function leads(Request $r)
    {
        $leads = Lead::for($r->user())->with('owner:id,name','branch:id,name')
            ->when($r->stage, fn ($q, $v) => $q->where('stage', $v))
            ->when($r->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('name','like',"%{$v}%")->orWhere('company','like',"%{$v}%")->orWhere('reference','like',"%{$v}%")))
            ->latest()->paginate(25)->withQueryString();
        return view('commercial::leads.index', ['leads' => $leads, 'stages' => Lead::STAGES]);
    }

    public function leadStore(Request $r, LeadService $svc)
    {
        $data = $r->validate([
            'name' => 'required|string|max:255', 'phone' => 'nullable|string|max:40', 'email' => 'nullable|email',
            'company' => 'nullable|string|max:255', 'source' => 'nullable|in:' . implode(',', Lead::SOURCES),
            'interested_service' => 'nullable|string|max:60', 'origin' => 'nullable|string|max:100',
            'destination' => 'nullable|string|max:100', 'estimated_value' => 'nullable|numeric|min:0',
            'priority' => 'nullable|in:low,normal,high', 'branch_id' => 'nullable|exists:branches,id',
            'follow_up_at' => 'nullable|date', 'notes' => 'nullable|string',
        ]);
        $lead = $svc->create($data, $r->user()->id);
        return redirect()->route('commercial.leads.index')->with('ok', "Lead {$lead->reference} created.");
    }

    public function leadTransition(Request $r, Lead $lead, LeadService $svc)
    {
        $data = $r->validate(['stage' => 'required|in:' . implode(',', Lead::STAGES), 'reason' => 'nullable|string|max:500']);
        $svc->transition($lead, $data['stage'], $r->user()->id, $data['reason'] ?? null);
        return back()->with('ok', 'Lead updated.');
    }

    public function leadConvert(Request $r, Lead $lead, LeadService $svc)
    {
        abort_if($lead->converted_customer_id, 422, 'Lead already converted.');
        $svc->convert($lead, $r->user()->id, $r->input('customer_id'));
        return redirect()->route('commercial.leads.index')->with('ok', 'Lead converted to customer.');
    }

    // ── Inquiries ──────────────────────────────────────────
    public function inquiries(Request $r)
    {
        $inquiries = Inquiry::for($r->user())->with('customer:id,name','branch:id,name','agent:id,name')
            ->when($r->status, fn ($q, $v) => $q->where('status', $v))
            ->when($r->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('reference','like',"%{$v}%")->orWhere('cargo_description','like',"%{$v}%")->orWhere('contact_person','like',"%{$v}%")))
            ->latest()->paginate(25)->withQueryString();
        return view('commercial::inquiries.index', compact('inquiries'));
    }

    public function inquiryShow(Inquiry $inquiry)
    {
        $inquiry->load(['customer:id,code,name','branch:id,name','agent:id,name','activities.user:id,name','attachments','latestAssessment','quotations:id,inquiry_id,number,status,total,currency']);
        return view('commercial::inquiries.show', compact('inquiry'));
    }

    public function inquiryCreate() { return view('commercial::inquiries.form', ['inquiry' => new Inquiry]); }

    public function inquiryStore(Request $r, InquiryService $svc)
    {
        $data = $r->validate([
            'customer_id' => 'nullable|exists:customers,id', 'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:40', 'email' => 'nullable|email',
            'branch_id' => 'nullable|exists:branches,id', 'origin_country' => 'nullable|string|max:100',
            'origin_city' => 'nullable|string|max:100', 'destination_country' => 'nullable|string|max:100',
            'destination_city' => 'nullable|string|max:100', 'cargo_category' => 'nullable|in:' . implode(',', Inquiry::CATEGORIES),
            'cargo_description' => 'nullable|string', 'package_count' => 'nullable|integer|min:0',
            'estimated_weight' => 'nullable|numeric|min:0', 'cargo_value' => 'nullable|numeric|min:0',
            'service_type' => 'nullable|string|max:50', 'expected_ship_date' => 'nullable|date',
            'special_handling' => 'nullable|string', 'source' => 'required|in:' . implode(',', Inquiry::SOURCES),
            'priority' => 'required|in:low,normal,high',
        ]);
        $inq = $svc->create($data, $r->user()->id);
        return redirect()->route('commercial.inquiries.show', $inq)->with('ok', "Inquiry {$inq->reference} created.");
    }

    public function inquiryTransition(Request $r, Inquiry $inquiry, InquiryService $svc)
    {
        $data = $r->validate(['status' => 'required|in:' . implode(',', Inquiry::STATUSES), 'note' => 'nullable|string|max:500']);
        $svc->transition($inquiry, $data['status'], $r->user()->id, $data['note'] ?? null);
        return back()->with('ok', 'Inquiry updated.');
    }

    public function inquiryAssess(Request $r, Inquiry $inquiry)
    {
        $data = $r->validate([
            'fragile' => 'boolean', 'liquid' => 'boolean', 'battery' => 'boolean', 'perishable' => 'boolean',
            'dangerous_declared' => 'boolean', 'packaging_requirements' => 'nullable|string',
            'outcome' => 'required|in:' . implode(',', \Modules\Commercial\Models\CargoAssessment::OUTCOMES),
            'conditions' => 'nullable|string',
        ]);
        $a = $inquiry->assessments()->create($data + ['assessed_by' => $r->user()->id]);
        app(InquiryService::class)->log($inquiry, 'assessed', "Assessment: {$a->outcome}", $r->user()->id);
        if ($a->outcome === 'eligible' || $a->outcome === 'eligible_conditions') {
            if ($inquiry->canTransitionTo('ready_for_quote')) $inquiry->update(['status' => 'ready_for_quote']);
        }
        return back()->with('ok', 'Assessment recorded.');
    }

    // ── Quotations ─────────────────────────────────────────
    public function quotations(Request $r)
    {
        $quotations = Quotation::for($r->user())->with('customer:id,name','branch:id,name')
            ->when($r->status, fn ($q, $v) => $q->where('status', $v))
            ->when($r->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('number','like',"%{$v}%")->orWhereHas('customer', fn ($c) => $c->where('name','like',"%{$v}%"))))
            ->latest()->paginate(25)->withQueryString();
        return view('commercial::quotations.index', compact('quotations'));
    }

    public function quotationShow(Quotation $quotation)
    {
        $quotation->load(['customer','branch:id,name','items','events.user:id,name','inquiry:id,reference','parent:id,number','versions:id,parent_id,number,version,status','handover']);
        return view('commercial::quotations.show', compact('quotation'));
    }

    public function quotationCreate(Request $r) { return view('commercial::quotations.form', [
        'quotation' => new Quotation(['inquiry_id' => $r->inquiry]),
        'inquiry' => $r->inquiry ? Inquiry::find($r->inquiry) : null,
    ]); }

    public function quotationStore(Request $r, QuotationService $svc)
    {
        $data = $r->validate([
            'customer_id' => 'required|exists:customers,id', 'inquiry_id' => 'nullable|exists:inquiries,id',
            'branch_id' => 'nullable|exists:branches,id', 'origin' => 'nullable|string|max:150',
            'destination' => 'nullable|string|max:150', 'cargo_description' => 'nullable|string',
            'package_count' => 'nullable|integer|min:0', 'gross_weight' => 'nullable|numeric|min:0',
            'volumetric_weight' => 'nullable|numeric|min:0', 'rate_card_id' => 'nullable|exists:rate_cards,id',
            'discount' => 'nullable|numeric|min:0', 'valid_until' => 'nullable|date|after:today',
            'payment_terms' => 'nullable|string', 'service_terms' => 'nullable|string', 'notes' => 'nullable|string',
        ]);
        $q = $svc->create($data, $r->user()->id);
        return redirect()->route('commercial.quotations.show', $q)->with('ok', "Quotation {$q->number} created.");
    }

    public function quotationTransition(Request $r, Quotation $quotation, QuotationService $svc)
    {
        $to = $r->validate(['status' => 'required|in:' . implode(',', Quotation::STATUSES)])['status'];
        if ($to === 'approved') abort_unless($r->user()->can('quotation.approve'), 403);
        if ($to === 'sent') abort_unless($r->user()->can('quotation.send'), 403);
        $svc->transition($quotation, $to, $r->user()->id);
        return back()->with('ok', "Quotation {$to}.");
    }

    public function quotationPrint(Quotation $quotation)
    {
        $quotation->load(['customer','branch','items','preparer:id,name']);
        return view('commercial::quotations.print', compact('quotation'));
    }

    // ── Rate cards ─────────────────────────────────────────
    public function rateCards(Request $r)
    {
        return view('commercial::rate-cards.index', ['cards' => RateCard::with('branch:id,name')->latest()->paginate(25)]);
    }

    public function rateCardStore(Request $r)
    {
        $data = $r->validate([
            'code' => 'required|string|max:40|unique:rate_cards,code', 'name' => 'required|string|max:255',
            'origin_country' => 'nullable|string|max:100', 'destination_country' => 'nullable|string|max:100',
            'service_type' => 'nullable|string|max:50', 'cargo_category' => 'nullable|string|max:50',
            'currency' => 'required|string|size:3', 'base_rate' => 'nullable|numeric|min:0',
            'per_kg_rate' => 'nullable|numeric|min:0', 'minimum_charge' => 'nullable|numeric|min:0',
            'volumetric_divisor' => 'nullable|integer|min:1', 'branch_id' => 'nullable|exists:branches,id',
            'effective_from' => 'nullable|date', 'effective_to' => 'nullable|date|after_or_equal:effective_from',
        ]);
        RateCard::create($data + ['status' => 'draft']);
        return back()->with('ok', 'Rate card created (draft).');
    }

    public function rateCardApprove(Request $r, RateCard $rateCard)
    {
        abort_unless($r->user()->can('pricing.approve'), 403);
        $rateCard->update(['status' => 'active', 'approved_by' => $r->user()->id, 'approved_at' => now()]);
        return back()->with('ok', "Rate card {$rateCard->code} activated.");
    }

    // ── Public acceptance (token link, no auth) ────────────
    public function publicQuote(string $token)
    {
        $quotation = Quotation::where('acceptance_token', $token)->with('customer','items','branch')->firstOrFail();
        if ($quotation->status === 'sent') { $quotation->update(['status' => 'viewed']); app(QuotationService::class)->log($quotation, 'viewed', null); }
        return view('commercial::quotations.public', compact('quotation'));
    }

    public function publicQuoteRespond(Request $r, string $token, QuotationService $svc)
    {
        $q = Quotation::where('acceptance_token', $token)->firstOrFail();
        $action = $r->validate(['action' => 'required|in:accept,reject'])['action'];
        try {
            if ($action === 'accept') $svc->accept($q, 'portal_link');
            else $svc->transition($q, 'rejected', 0);
        } catch (\Throwable $e) {
            return back()->with('err', $e->getMessage());
        }
        return back()->with('ok', $action === 'accept' ? 'Quotation accepted. Asante!' : 'Quotation declined.');
    }
}
