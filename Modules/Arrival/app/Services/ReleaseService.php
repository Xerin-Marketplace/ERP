<?php

namespace Modules\Arrival\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Arrival\Models\ReleaseApproval;
use Modules\Cargo\Models\CargoPackage;

/**
 * Release control (spec 12.8): system confirms the invoice is fully paid
 * (or credit approved), then an approver confirms release. The release code
 * (6-digit OTP) cannot be generated, and delivery cannot start, without
 * Approved status.
 */
class ReleaseService
{
    public function __construct(private AuditService $audit) {}

    /** Request release for a package — checks payment status first. */
    public function request(CargoPackage $package, ?int $invoiceId, int $branchId, int $userId): ReleaseApproval
    {
        if ($open = ReleaseApproval::where('package_id', $package->id)->where('status', 'pending')->first()) {
            return $open; // idempotent
        }
        if ($invoiceId) {
            $invoice = \Modules\Finance\Models\CustomerInvoice::findOrFail($invoiceId);
            if (! in_array($invoice->status, ['paid', 'issued'], true)
                || (float) $invoice->received_amount < (float) $invoice->total) {
                throw ValidationException::withMessages(['invoice' =>
                    "Invoice {$invoice->reference} is not fully paid — release cannot be requested."]);
            }
        }
        return ReleaseApproval::create([
            'reference' => $this->nextRef(), 'package_id' => $package->id,
            'invoice_id' => $invoiceId, 'branch_id' => $branchId, 'status' => 'pending',
        ]);
    }

    /** Approver decision. Approved → generate 6-digit release code. */
    public function decide(ReleaseApproval $approval, int $userId, string $decision, ?string $reason = null): ReleaseApproval
    {
        abort_unless($approval->status === 'pending', 422, 'Already decided.');
        abort_unless(in_array($decision, ['approved', 'on_hold', 'rejected'], true), 422);
        abort_if(in_array($decision, ['on_hold', 'rejected'], true) && ! trim((string) $reason),
            422, 'A reason is mandatory for hold or rejection.');

        $approval->update([
            'status' => $decision, 'reason' => $reason,
            'decided_by' => $userId, 'decided_at' => now(),
            'release_code' => $decision === 'approved' ? str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT) : null,
        ]);
        $this->audit->log("release.{$decision}", $approval,
            new: ['reason' => $reason, 'package_id' => $approval->package_id]);
        return $approval;
    }

    /** Hard gate called before dispatch/collection — spec 14.4. */
    public function canRelease(CargoPackage $package): bool
    {
        return ReleaseApproval::where('package_id', $package->id)
            ->where('status', 'approved')->whereNull('code_used_at')->exists();
    }

    /** Verify the customer's OTP at hand-over; burns the code. */
    public function verifyCode(CargoPackage $package, string $code): bool
    {
        $a = ReleaseApproval::where('package_id', $package->id)
            ->where('status', 'approved')->where('release_code', $code)
            ->whereNull('code_used_at')->first();
        if (! $a) return false;
        $a->update(['status' => 'released', 'code_used_at' => now()]);
        $this->audit->log('release.code_used', $a);
        return true;
    }

    private function nextRef(): string
    {
        $p = 'RA-'.now()->format('Y').'-';
        $max = ReleaseApproval::where('reference', 'like', "{$p}%")->lockForUpdate()->max('reference');
        return sprintf('%s%05d', $p, $max ? ((int) substr($max, -5)) + 1 : 1);
    }
}
