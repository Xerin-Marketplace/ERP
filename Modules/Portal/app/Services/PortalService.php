<?php
namespace Modules\Portal\Services;

use Illuminate\Support\Facades\DB;
use Modules\Portal\Models\{Complaint, ComplaintEvent, CargoClaim, ClaimEvent, CustomerFeedback,
    CommunicationPreference, CustomerMessage, PrivacyRequest};
use Modules\Commercial\Models\Customer;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketComment;

class PortalService
{
    public function ref(string $table, string $prefix): string
    {
        $full = "{$prefix}-" . now()->format('Y') . "-";
        $max = DB::table($table)->where('reference', 'like', "{$full}%")->orderByDesc('reference')->lockForUpdate()->value('reference');
        return sprintf('%s%06d', $full, $max ? ((int) substr($max, -6)) + 1 : 1);
    }

    // ── Customer identity (portal user → customer record) ──
    public function customerFor($user): ?Customer
    {
        return Customer::where('user_id', $user->id)->first();
    }

    // ── Customer dashboard — real cross-module data ──────
    public function customerDashboard(Customer $c): array
    {
        $pkgs = \Modules\Cargo\Models\CargoPackage::where('customer_id', $c->id);
        $orders = \Modules\Delivery\Models\DeliveryOrder::where('customer_id', $c->id);
        $claims = CargoClaim::where('customer_id', $c->id);
        $complaints = Complaint::where('customer_id', $c->id);
        $tickets = Ticket::where('customer_id', $c->id);
        $invoices = \Modules\Finance\Models\CustomerInvoice::where('customer_id', $c->id);
        return [
            'packages_total' => (clone $pkgs)->count(),
            'packages_in_transit' => (clone $pkgs)->whereIn('status', ['allocated','dispatched'])->count(),
            'packages_in_warehouse' => (clone $pkgs)->where('status', 'received_tz')->count(),
            'packages_delivered' => (clone $pkgs)->whereIn('status', ['delivered','collected'])->count(),
            'active_deliveries' => (clone $orders)->whereIn('status', ['assigned','driver_accepted','picked_up','in_transit','arrived_at_destination'])->count(),
            'open_tickets' => (clone $tickets)->whereNotIn('status', ['resolved','closed'])->count(),
            'open_complaints' => (clone $complaints)->whereNotIn('status', ['resolved','closed','rejected'])->count(),
            'pending_claims' => (clone $claims)->whereNotIn('status', ['settled','closed','rejected'])->count(),
            'outstanding_balance' => (clone $invoices)->where('status', '!=', 'paid')->sum(DB::raw('total - received_amount')),
        ];
    }

    // ── Customer-visible tracking timeline ───────────────
    /** Verified milestones only — no internal ops detail. */
    public function trackingTimeline(Customer $c): array
    {
        return \Modules\Cargo\Models\CargoPackage::where('customer_id', $c->id)
            ->get()->map(fn ($p) => ['package' => $p->reference, 'status' => $p->status,
                'label' => match ($p->status) {
                    'stored' => 'Received at warehouse', 'allocated' => 'Assigned to shipment',
                    'dispatched' => 'Handed to airline', 'received_tz' => 'In Tanzania warehouse',
                    'out_for_delivery' => 'Out for delivery', 'delivered' => 'Delivered',
                    'collected' => 'Collected by customer', 'on_hold' => 'On hold',
                    default => $p->status,
                }])->all();
    }

    // ── Support ticket (reuses Ticketing engine) ─────────
    public function createTicket(Customer $c, array $data): Ticket
    {
        return DB::transaction(function () use ($c, $data) {
            $t = new Ticket([
                'subject' => $data['subject'], 'description' => $data['description'],
                'priority' => $data['priority'] ?? 'normal', 'status' => 'submitted',
                'source' => 'customer_portal', 'requester_id' => $c->user_id,
                'related' => $data['related'] ?? null,
            ]);
            $full = 'XER-TKT-' . now()->format('Y') . '-';
            $max = Ticket::where('ticket_number', 'like', "{$full}%")->orderByDesc('ticket_number')->lockForUpdate()->value('ticket_number');
            $t->forceFill(['customer_id' => $c->id,
                'ticket_number' => sprintf('%s%06d', $full, $max ? ((int) substr($max, -6)) + 1 : 1)])->save();
            return $t;
        });
    }

    public function ticketReply(Ticket $t, string $body, ?int $userId, bool $internal = false): TicketComment
    {
        abort_if($internal && ! $userId, 403, 'Internal notes require staff.');
        $author = $userId ?? $t->customer?->user_id ?? 0;
        return $t->comments()->create(['body' => $body, 'author_id' => $author, 'is_internal' => $internal, 'kind' => $internal ? 'internal' : 'reply']);
    }

    // ── Complaints ───────────────────────────────────────
    public function submitComplaint(Customer $c, array $data, ?int $userId = null): Complaint
    {
        $cp = new Complaint($data + ['customer_id' => $c->id]);
        $cp->forceFill(['reference' => $this->ref('complaints', 'XER-CMP')])->save();
        $this->complaintEvent($cp, 'submitted', $userId, 'Complaint received', 'customer');
        return $cp;
    }

    public function complaintEvent(Complaint $cp, string $event, ?int $userId, ?string $note = null, string $visibility = 'internal'): void
    {
        ComplaintEvent::create(['complaint_id' => $cp->id, 'event' => $event, 'user_id' => $userId, 'note' => $note, 'visibility' => $visibility]);
    }

    public function transitionComplaint(Complaint $cp, string $to, int $userId, ?string $note = null): Complaint
    {
        abort_unless($cp->canTransitionTo($to), 422, "Cannot move {$cp->status} → {$to}");
        $cp->update(['status' => $to] + ($to === 'resolved' && $note ? ['resolution' => $note] : []));
        $this->complaintEvent($cp, "status:{$to}", $userId, $note, in_array($to, ['resolved','rejected','closed']) ? 'customer' : 'internal');
        return $cp;
    }

    // ── Claims — with separation of duties ───────────────
    public function submitClaim(Customer $c, array $data): CargoClaim
    {
        validator($data, ['type' => 'required|in:lost,damaged,missing_items,partial_delivery,incorrect_delivery,other',
            'claimed_amount' => 'required|numeric|min:0', 'description' => 'required|string'])->validate();
        $cl = new CargoClaim($data + ['customer_id' => $c->id]);
        $cl->forceFill(['reference' => $this->ref('cargo_claims', 'XER-CLM')])->save();
        $this->claimEvent($cl, 'submitted', $c->user_id);
        return $cl;
    }

    public function claimEvent(CargoClaim $cl, string $event, ?int $userId, ?string $note = null, ?string $evidence = null): void
    {
        ClaimEvent::create(['claim_id' => $cl->id, 'event' => $event, 'user_id' => $userId, 'note' => $note, 'evidence' => $evidence]);
    }

    public function investigateClaim(CargoClaim $cl, int $userId, string $notes): CargoClaim
    {
        abort_unless($cl->canTransitionTo('pending_approval'), 422, 'Claim not in an investigable state.');
        $cl->update(['investigator_id' => $userId, 'investigation_notes' => $notes, 'status' => 'pending_approval']);
        $this->claimEvent($cl, 'investigation_complete', $userId, $notes);
        return $cl;
    }

    /** Approve/reject — approver must differ from investigator (separation of duties). */
    public function decideClaim(CargoClaim $cl, string $decision, int $userId, string $reason, ?float $amount = null): CargoClaim
    {
        abort_unless(in_array($decision, ['approved','partially_approved','rejected'], true), 422);
        abort_unless($cl->status === 'pending_approval', 422, 'Claim must reach pending_approval.');
        abort_if($cl->investigator_id === $userId, 403, 'Investigator cannot decide their own claim.');
        if ($decision === 'partially_approved') {
            abort_if($amount === null || $amount <= 0 || $amount >= $cl->claimed_amount, 422, 'Partial approval needs an amount below the claim.');
        }
        $cl->update(['status' => $decision, 'decision_by' => $userId, 'decision_at' => now(),
            'decision_reason' => $reason, 'approved_amount' => $decision === 'approved' ? $cl->claimed_amount : $amount]);
        $this->claimEvent($cl, "decision:{$decision}", $userId, $reason);
        return $cl;
    }

    /** Settlement request — records intent; Finance posts the actual credit/payment. */
    public function requestSettlement(CargoClaim $cl, int $userId): CargoClaim
    {
        abort_unless(in_array($cl->status, ['approved','partially_approved'], true), 422, 'Claim not approved.');
        abort_if(empty($cl->approved_amount), 422);
        $cl->update(['status' => 'settlement_pending']);
        $this->claimEvent($cl, 'settlement_requested', $userId, "Amount: {$cl->approved_amount} {$cl->currency}");
        return $cl;
    }

    public function markSettled(CargoClaim $cl, string $settlementRef, int $userId): CargoClaim
    {
        abort_if(empty($settlementRef), 422, 'Settlement reference (Finance payment/credit) required.');
        abort_unless($cl->status === 'settlement_pending', 422);
        $cl->update(['status' => 'settled', 'settlement_ref' => $settlementRef]);
        $this->claimEvent($cl, 'settled', $userId, "Ref: {$settlementRef}");
        return $cl;
    }

    // ── Feedback — deduped per survey event ──────────────
    public function submitFeedback(Customer $c, string $surveyKey, int $rating, array $data = []): CustomerFeedback
    {
        abort_if(CustomerFeedback::where('customer_id', $c->id)->where('survey_key', $surveyKey)->exists(), 422, 'Feedback already submitted.');
        abort_if($rating < 1 || $rating > 5, 422);
        return CustomerFeedback::create($data + ['customer_id' => $c->id, 'survey_key' => $surveyKey, 'rating' => $rating]);
    }

    // ── Messages — never claim delivered prematurely ────
    public function queueMessage(Customer $c, string $channel, string $body, array $data = []): CustomerMessage
    {
        abort_unless(in_array($channel, ['whatsapp','sms','email','in_app'], true), 422);
        // respect preferences for non-operational channels
        $prefs = $c->communicationPreference ?? null;
        if ($prefs && ($data['operational'] ?? true) === false && ! ($prefs->channels[$channel] ?? true)) {
            abort(422, "Customer has disabled {$channel}.");
        }
        return CustomerMessage::create($data + ['customer_id' => $c->id, 'channel' => $channel,
            'body' => $body, 'status' => 'queued']);
    }

    // ── Privacy requests ─────────────────────────────────
    public function submitPrivacyRequest(Customer $c, string $type, ?string $details): PrivacyRequest
    {
        abort_unless(in_array($type, ['access','correction','export','closure','preferences'], true), 422);
        $p = new PrivacyRequest(['customer_id' => $c->id, 'type' => $type, 'details' => $details]);
        $p->forceFill(['reference' => $this->ref('privacy_requests', 'XER-PRV')])->save();
        return $p;
    }

    // ── Customer 360 (read-only aggregation) ────────────
    public function customer360(Customer $c): array
    {
        return [
            'profile' => $c,
            'addresses' => $c->addresses ?? collect(),
            'quotations' => \Modules\Commercial\Models\Quotation::where('customer_id', $c->id)->latest()->limit(10)->get(),
            'packages' => \Modules\Cargo\Models\CargoPackage::where('customer_id', $c->id)->latest()->limit(20)->get(),
            'deliveries' => \Modules\Delivery\Models\DeliveryOrder::where('customer_id', $c->id)->latest()->limit(10)->get(),
            'invoices' => \Modules\Finance\Models\CustomerInvoice::where('customer_id', $c->id)->latest()->limit(10)->get(),
            'tickets' => Ticket::where('customer_id', $c->id)->latest()->limit(10)->get(),
            'complaints' => Complaint::where('customer_id', $c->id)->latest()->limit(10)->get(),
            'claims' => CargoClaim::where('customer_id', $c->id)->latest()->limit(10)->get(),
        ];
    }

    /** Statement — read-only from Finance records, no separate ledger. */
    public function statement(Customer $c, ?string $from = null, ?string $to = null): array
    {
        $inv = \Modules\Finance\Models\CustomerInvoice::where('customer_id', $c->id)
            ->when($from, fn ($q) => $q->where('invoice_date', '>=', $from))
            ->when($to, fn ($q) => $q->where('invoice_date', '<=', $to))
            ->orderBy('invoice_date')->get();
        return [
            'customer' => $c->only(['id','code','name']),
            'invoices' => $inv->map(fn ($i) => [
                'reference' => $i->reference, 'date' => $i->invoice_date, 'total' => $i->total,
                'received' => $i->received_amount, 'balance' => $i->total - $i->received_amount, 'status' => $i->status])->all(),
            'closing_balance' => $inv->sum(fn ($i) => $i->total - $i->received_amount),
        ];
    }
}
