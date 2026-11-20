<?php

namespace Modules\Commercial\Services;

use Illuminate\Support\Facades\DB;
use Modules\Commercial\Models\Inquiry;
use Modules\Commercial\Models\InquiryActivity;

class InquiryService
{
    public function __construct(private SequenceService $seq) {}

    public function create(array $data, int $userId): Inquiry
    {
        return DB::transaction(function () use ($data, $userId) {
            $inq = new Inquiry($data + ['status' => 'new']);
            $inq->forceFill(['reference' => $this->seq->inquiry()])->save();
            $this->log($inq, 'created', 'Inquiry registered', $userId);
            return $inq;
        });
    }

    public function transition(Inquiry $inq, string $to, int $userId, ?string $note = null): Inquiry
    {
        abort_unless($inq->canTransitionTo($to), 422, "Cannot move inquiry from {$inq->status} to {$to}");
        $from = $inq->status;
        $inq->update(['status' => $to]);
        $this->log($inq, 'status_change', "{$from} → {$to}" . ($note ? " — {$note}" : ''), $userId);
        return $inq;
    }

    public function assign(Inquiry $inq, ?int $agentId, int $userId): Inquiry
    {
        $inq->update(['agent_id' => $agentId]);
        $this->log($inq, 'assigned', $agentId ? "Assigned to user #{$agentId}" : 'Unassigned', $userId);
        return $inq;
    }

    public function log(Inquiry $inq, string $event, string $body, ?int $userId, array $meta = []): void
    {
        InquiryActivity::create(['inquiry_id' => $inq->id, 'user_id' => $userId, 'event' => $event, 'body' => $body, 'meta' => $meta ?: null]);
    }
}
