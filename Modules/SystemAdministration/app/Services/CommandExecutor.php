<?php

namespace Modules\SystemAdministration\Services;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\SystemAdministration\Models\CommandApproval;
use Modules\SystemAdministration\Models\CommandExecution;

/**
 * Executes registry commands through the controlled lifecycle:
 * validate params → (high/critical: pending_approval) → run handler →
 * record output/duration/status → audit.
 *
 * Safety: handlers are PHP callables from CommandCatalog only — no user
 * strings, no shell. Sensitive params (keys containing
 * password|secret|token|key) are redacted before persistence.
 */
class CommandExecutor
{
    private const REDACT = '/password|secret|token|key|credential/i';

    public function __construct(private \App\Services\AuditService $audit) {}

    /** Validate params + create execution; runs immediately unless approval required. */
    public function submit(string $commandId, array $params, User $actor, ?string $reason = null): CommandExecution
    {
        $def = CommandRegistry::mustFind($commandId);
        if (isset($def['permission']) && ! $actor->can($def['permission'])) {
            throw ValidationException::withMessages(['command' => 'Permission denied for this command.']);
        }

        $schema = $def['parameters'] ?? [];
        if ($schema) {
            $v = Validator::make($params, $schema);
            if ($v->fails()) {
                throw ValidationException::withMessages($v->errors()->toArray());
            }
            $params = $v->validated();
        }

        $needsApproval = ($def['requires_approval'] ?? false) || ($def['risk'] ?? 'low') === 'critical';
        if (in_array($def['risk'] ?? 'low', ['high', 'critical'], true) && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reason is required for high-risk commands.']);
        }

        // Idempotency: one pending/running execution per command+user.
        $open = CommandExecution::where('command_id', $commandId)
            ->where('user_id', $actor->id)
            ->whereIn('status', ['pending_approval', 'running'])->exists();
        if ($open) {
            throw ValidationException::withMessages(['command' => 'A pending or running execution already exists.']);
        }

        $exec = CommandExecution::create([
            'command_id' => $commandId,
            'user_id' => $actor->id,
            'params' => $this->redact($params),
            'risk' => $def['risk'] ?? 'low',
            'status' => $needsApproval ? 'pending_approval' : 'running',
            'reason' => $reason,
            'ip_address' => request()?->ip(),
            'correlation_id' => (string) Str::uuid(),
        ]);

        if ($needsApproval) {
            CommandApproval::create(['execution_id' => $exec->id]);
            $this->audit->log('command.approval_requested', $exec, null,
                ['command' => $commandId, 'risk' => $exec->risk]);
            return $exec;
        }

        return $this->run($exec, $def, $params);
    }

    /** Approve + execute a pending execution. Requester cannot self-approve. */
    public function approve(CommandExecution $exec, User $approver, ?string $comment = null): CommandExecution
    {
        abort_unless($approver->can('sysadmin.commands.approve'), 403);
        if ($exec->status !== 'pending_approval') {
            throw ValidationException::withMessages(['execution' => 'Not awaiting approval.']);
        }
        if ($exec->user_id === $approver->id) {
            throw ValidationException::withMessages(['approval' => 'You cannot approve your own command.']);
        }
        $exec->approval->update([
            'decision' => 'approved', 'decided_by' => $approver->id,
            'comment' => $comment, 'decided_at' => now(),
        ]);
        $exec->update(['status' => 'running']);
        $this->audit->log('command.approved', $exec);
        return $this->run($exec, CommandRegistry::mustFind($exec->command_id), $exec->params ?? []);
    }

    public function reject(CommandExecution $exec, User $approver, ?string $comment = null): void
    {
        abort_unless($approver->can('sysadmin.commands.approve'), 403);
        if ($exec->status !== 'pending_approval') {
            throw ValidationException::withMessages(['execution' => 'Not awaiting approval.']);
        }
        $exec->approval->update([
            'decision' => 'rejected', 'decided_by' => $approver->id,
            'comment' => $comment, 'decided_at' => now(),
        ]);
        $exec->update(['status' => 'rejected']);
        $this->audit->log('command.rejected', $exec);
    }

    /** Run handler; capture output/error/duration. */
    public function run(CommandExecution $exec, array $def, array $params): CommandExecution
    {
        $exec->update(['status' => 'running', 'started_at' => now()]);
        $t = microtime(true);
        try {
            $out = ($def['handler'])($params);
            $exec->update([
                'status' => 'completed',
                'output' => is_string($out) ? $out : json_encode($out, JSON_PRETTY_PRINT),
                'duration_ms' => (int) ((microtime(true) - $t) * 1000),
                'finished_at' => now(),
            ]);
            $this->audit->log('command.executed', $exec, null, ['command' => $exec->command_id]);
        } catch (\Throwable $e) {
            $exec->update([
                'status' => 'failed',
                'error' => Str::limit($e->getMessage(), 2000),
                'duration_ms' => (int) ((microtime(true) - $t) * 1000),
                'finished_at' => now(),
            ]);
            $this->audit->log('command.failed', $exec, null, ['command' => $exec->command_id]);
        }
        return $exec;
    }

    private function redact(array $params): array
    {
        return collect($params)->mapWithKeys(fn ($v, $k) =>
            [$k => preg_match(self::REDACT, (string) $k) ? '•••' : $v])->all();
    }
}
