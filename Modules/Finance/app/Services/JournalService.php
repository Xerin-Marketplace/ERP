<?php

namespace Modules\Finance\Services;

use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Finance\Models\ChartOfAccount;
use Modules\Finance\Models\FinancialPeriod;
use Modules\Finance\Models\FinancialSetting;
use Modules\Finance\Models\GeneralLedgerEntry;
use Modules\Finance\Models\Journal;
use Modules\Finance\Models\JournalLine;

/**
 * Double-entry accounting engine.
 * Enforces: balanced journals, valid/postable/active accounts, open periods,
 * immutability of posted entries, single-post idempotency, controlled reversal.
 */
class JournalService
{
    public function __construct(
        private FinNumberService $numbers,
        private AuditService $audit,
    ) {}

    /** lines: [['account_id'=>, 'debit'=>, 'credit'=>, 'description'=>?, 'branch_id'=>?, 'department_id'=>?]] */
    public function create(array $data, int $userId): Journal
    {
        $this->validateLines($data['lines'] ?? []);
        $period = FinancialPeriod::findOrFail($data['period_id']);

        return DB::transaction(function () use ($data, $userId, $period) {
            $journal = Journal::create([
                'reference'    => $this->numbers->for('journal'),
                'company_id'   => $data['company_id'],
                'period_id'    => $period->id,
                'type'         => $data['type'] ?? 'general',
                'journal_date' => $data['journal_date'],
                'currency'     => $data['currency'] ?? 'TZS',
                'exchange_rate' => $data['exchange_rate'] ?? 1,
                'memo'         => $data['memo'] ?? null,
                'status'       => 'draft',
                'source_type'  => $data['source_type'] ?? null,
                'source_id'    => $data['source_id'] ?? null,
                'created_by'   => $userId,
            ]);
            foreach ($data['lines'] as $line) {
                $journal->lines()->create([
                    'account_id'    => $line['account_id'],
                    'branch_id'     => $line['branch_id'] ?? null,
                    'department_id' => $line['department_id'] ?? null,
                    'description'   => $line['description'] ?? null,
                    'debit'         => (float) ($line['debit'] ?? 0),
                    'credit'        => (float) ($line['credit'] ?? 0),
                ]);
            }
            $this->audit->log('finance.journal.created', $journal, new: ['ref' => $journal->reference]);
            return $journal;
        });
    }

    /** Posted journals are immutable; drafts/rejected may edit lines. */
    public function updateLines(Journal $journal, array $lines): Journal
    {
        if (! in_array($journal->status, ['draft', 'rejected'], true)) {
            throw ValidationException::withMessages(['journal' => 'Only draft or rejected journals can be edited.']);
        }
        $this->validateLines($lines);
        return DB::transaction(function () use ($journal, $lines) {
            $journal->lines()->delete();
            foreach ($lines as $line) {
                $journal->lines()->create([
                    'account_id'    => $line['account_id'],
                    'branch_id'     => $line['branch_id'] ?? null,
                    'department_id' => $line['department_id'] ?? null,
                    'description'   => $line['description'] ?? null,
                    'debit'         => (float) ($line['debit'] ?? 0),
                    'credit'        => (float) ($line['credit'] ?? 0),
                ]);
            }
            return $journal->fresh('lines');
        });
    }

    public function submit(Journal $journal, int $userId): Journal
    {
        if ($journal->status !== 'draft' && $journal->status !== 'rejected') {
            throw ValidationException::withMessages(['journal' => 'Only draft journals can be submitted.']);
        }
        $journal->update(['status' => 'submitted', 'submitted_by' => $userId, 'submitted_at' => now()]);
        $this->audit->log('finance.journal.submitted', $journal);
        return $journal;
    }

    public function approve(Journal $journal, int $userId): Journal
    {
        $journal = $journal->fresh();
        if ($journal->status !== 'submitted') {
            throw ValidationException::withMessages(['journal' => 'Journal must be submitted before approval.']);
        }
        if ($journal->created_by === $userId) {
            throw ValidationException::withMessages(['journal' => 'Separation of duties: you cannot approve your own journal.']);
        }
        $journal->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
        $this->audit->log('finance.journal.approved', $journal);
        return $journal;
    }

    public function reject(Journal $journal, int $userId, string $reason): Journal
    {
        if ($journal->status !== 'submitted') {
            throw ValidationException::withMessages(['journal' => 'Only submitted journals can be rejected.']);
        }
        $journal->update(['status' => 'rejected', 'rejected_by' => $userId, 'rejection_reason' => $reason]);
        $this->audit->log('finance.journal.rejected', $journal, new: ['reason' => $reason]);
        return $journal;
    }

    /**
     * Post to GL. Idempotent: unique journal_line_id on GL + locked status check
     * mean a second post() on the same journal can never double-write.
     */
    public function post(Journal $journal, int $userId): Journal
    {
        return DB::transaction(function () use ($journal, $userId) {
            /** @var Journal $journal */
            $journal = Journal::whereKey($journal->id)->lockForUpdate()->firstOrFail();
            // System-generated journals (source documents carry their own approval
            // workflow) may post from draft; manual journals follow the approval gate.
            $requiresApproval = (bool) (FinancialSetting::where('company_id', $journal->company_id)->value('require_journal_approval') ?? true)
                && ! $journal->source_type;
            $allowed = $requiresApproval ? ['approved'] : ['draft', 'submitted', 'approved'];
            if (! in_array($journal->status, $allowed, true)) {
                throw ValidationException::withMessages(['journal' => "Journal cannot be posted from status [{$journal->status}]."]);
            }
            if ($journal->posted_at) {
                throw ValidationException::withMessages(['journal' => 'Journal is already posted.']);
            }
            $this->assertPeriodOpen($journal);
            $this->assertBalanced($journal);
            $this->assertAccountsPostable($journal);

            foreach ($journal->lines as $line) {
                GeneralLedgerEntry::create([
                    'company_id'     => $journal->company_id,
                    'period_id'      => $journal->period_id,
                    'journal_id'     => $journal->id,
                    'journal_line_id' => $line->id,
                    'account_id'     => $line->account_id,
                    'branch_id'      => $line->branch_id,
                    'department_id'  => $line->department_id,
                    'entry_date'     => $journal->journal_date,
                    'debit'          => $line->debit,
                    'credit'         => $line->credit,
                    'reference'      => $journal->reference,
                    'description'    => $line->description ?? $journal->memo,
                ]);
            }
            $journal->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => now()]);
            $this->audit->log('finance.journal.posted', $journal, new: ['ref' => $journal->reference]);
            return $journal->fresh();
        });
    }

    /** Corrections to posted journals happen only via a posted reversal journal. */
    public function reverse(Journal $journal, int $userId, ?string $memo = null): Journal
    {
        return DB::transaction(function () use ($journal, $userId, $memo) {
            /** @var Journal $journal */
            $journal = Journal::whereKey($journal->id)->lockForUpdate()->firstOrFail();
            if ($journal->status !== 'posted') {
                throw ValidationException::withMessages(['journal' => 'Only posted journals can be reversed.']);
            }
            if ($journal->reversed_by_id) {
                throw ValidationException::withMessages(['journal' => 'Journal has already been reversed.']);
            }
            $reversal = Journal::create([
                'reference'      => $this->numbers->for('journal'),
                'company_id'     => $journal->company_id,
                'period_id'      => $journal->period_id,
                'type'           => 'reversal',
                'journal_date'   => $journal->journal_date,
                'currency'       => $journal->currency,
                'exchange_rate'  => $journal->exchange_rate,
                'memo'           => $memo ?? "Reversal of {$journal->reference}",
                'status'         => 'approved',
                'reversal_of_id' => $journal->id,
                'created_by'     => $userId,
                'approved_by'    => $userId,
                'approved_at'    => now(),
            ]);
            foreach ($journal->lines as $line) {
                $reversal->lines()->create([
                    'account_id'    => $line->account_id,
                    'branch_id'     => $line->branch_id,
                    'department_id' => $line->department_id,
                    'description'   => "Reversal: {$line->description}",
                    'debit'         => $line->credit,
                    'credit'        => $line->debit,
                ]);
            }
            $journal->update(['status' => 'reversed', 'reversed_by_id' => $reversal->id]);
            $this->audit->log('finance.journal.reversed', $journal, new: ['reversal' => $reversal->reference]);
            return $this->post($reversal, $userId);
        });
    }

    private function validateLines(array $lines): void
    {
        if (count($lines) < 2) {
            throw ValidationException::withMessages(['lines' => 'A journal requires at least two lines.']);
        }
        foreach ($lines as $i => $line) {
            $d = (float) ($line['debit'] ?? 0);
            $c = (float) ($line['credit'] ?? 0);
            if ($d < 0 || $c < 0 || ($d > 0 && $c > 0) || ($d === 0.0 && $c === 0.0)) {
                throw ValidationException::withMessages(["lines.{$i}" => 'Each line must have exactly one of debit or credit > 0.']);
            }
            if (empty($line['account_id'])) {
                throw ValidationException::withMessages(["lines.{$i}" => 'Account is required.']);
            }
        }
    }

    private function assertBalanced(Journal $journal): void
    {
        $debit = (float) $journal->lines()->sum('debit');
        $credit = (float) $journal->lines()->sum('credit');
        if (round($debit, 2) !== round($credit, 2) || $debit <= 0) {
            throw ValidationException::withMessages(['journal' => "Journal is unbalanced: debits {$debit} != credits {$credit}."]);
        }
    }

    private function assertPeriodOpen(Journal $journal): void
    {
        $period = $journal->period;
        if (! $period || $period->status !== 'open') {
            throw ValidationException::withMessages(['period' => 'Financial period is not open for posting.']);
        }
        $date = $journal->journal_date->toDateString();
        if ($date < $period->starts_on->toDateString() || $date > $period->ends_on->toDateString()) {
            throw ValidationException::withMessages(['period' => 'Journal date is outside the financial period.']);
        }
    }

    private function assertAccountsPostable(Journal $journal): void
    {
        $ids = $journal->lines->pluck('account_id')->unique();
        $accounts = ChartOfAccount::whereIn('id', $ids)->get()->keyBy('id');
        foreach ($ids as $id) {
            $a = $accounts->get($id);
            if (! $a) {
                throw ValidationException::withMessages(['account' => "Account {$id} does not exist."]);
            }
            if ($a->company_id !== $journal->company_id) {
                throw ValidationException::withMessages(['account' => "Account {$a->code} belongs to a different company."]);
            }
            if (! $a->is_active) {
                throw ValidationException::withMessages(['account' => "Account {$a->code} is inactive."]);
            }
            if (! $a->postable) {
                throw ValidationException::withMessages(['account' => "Account {$a->code} is a control account and cannot receive direct postings."]);
            }
        }
    }
}
