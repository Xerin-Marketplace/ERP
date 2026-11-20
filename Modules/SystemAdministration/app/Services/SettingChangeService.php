<?php

namespace Modules\SystemAdministration\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\SystemAdministration\Models\SettingChangeRequest;
use Modules\SystemAdministration\Models\SettingVersion;

/**
 * High/critical-risk settings governance: request → approve → publish.
 * Self-approval blocked; stale base_version → 'stale', publish re-validates.
 */
class SettingChangeService
{
    public function __construct(private ScopedSettingsResolver $resolver,
        private \App\Services\SettingsService $settings,
        private \App\Services\AuditService $audit) {}

    /** Directly apply low/medium-risk; high/critical → pending request. */
    public function requestChange(string $key, $value, User $actor, string $reason, string $scopeType = 'global', ?int $scopeId = null): SettingChangeRequest
    {
        $def = $this->resolver->allowed($key);
        \Illuminate\Support\Facades\Validator::make(['v' => $value], ['v' => $def['rules']])->validate();
        $risk = $def['risk'] ?? 'low';
        $cr = SettingChangeRequest::create([
            'key' => $key, 'scope_type' => $scopeType, 'scope_id' => $scopeId,
            'old_value' => $this->settings->get($key),
            'new_value' => is_scalar($value) ? (string) $value : json_encode($value),
            'risk' => $risk, 'requester_id' => $actor->id, 'reason' => $reason,
            'base_version' => SettingVersion::where('key', $key)->count(),
            'status' => in_array($risk, ['high', 'critical'], true) ? 'pending' : 'approved',
        ]);
        if ($cr->status === 'approved') {
            return $this->publish($cr->fresh());
        }
        return $cr;
    }

    public function approve(SettingChangeRequest $cr, User $approver, ?string $comment = null): SettingChangeRequest
    {
        if ($cr->requester_id === $approver->id) {
            throw ValidationException::withMessages(['approval' => 'Requester cannot approve their own change.']);
        }
        if ($cr->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Not awaiting approval.']);
        }
        if (SettingVersion::where('key', $cr->key)->count() !== $cr->base_version) {
            $cr->update(['status' => 'stale']);
            throw ValidationException::withMessages(['version' => 'Setting changed since submission — resubmit required.']);
        }
        $cr->update(['status' => 'approved', 'approver_id' => $approver->id,
            'approver_comment' => $comment, 'decided_at' => now()]);
        $this->audit->log('settings.change_approved', $cr);
        return $this->publish($cr);
    }

    public function reject(SettingChangeRequest $cr, User $approver, ?string $comment = null): void
    {
        if ($cr->status !== 'pending') {
            throw ValidationException::withMessages(['status' => 'Not awaiting approval.']);
        }
        $cr->update(['status' => 'rejected', 'approver_id' => $approver->id,
            'approver_comment' => $comment, 'decided_at' => now()]);
        $this->audit->log('settings.change_rejected', $cr);
    }

    /** Transactional publish: revalidate → set → version → audit. */
    public function publish(SettingChangeRequest $cr): SettingChangeRequest
    {
        $def = $this->resolver->allowed($cr->key);
        return DB::transaction(function () use ($cr, $def) {
            \Illuminate\Support\Facades\Validator::make(['v' => $cr->new_value], ['v' => $def['rules']])->validate();
            $this->settings->set($cr->key, $cr->new_value, explode('.', $cr->key)[0],
                match ($def['type']) { 'bool' => 'bool', 'int' => 'int', 'float' => 'float', 'multiselect','json' => 'json', default => 'string' });
            SettingVersion::create(['key' => $cr->key, 'group' => $def['group'],
                'old_value' => $cr->old_value, 'new_value' => $cr->new_value,
                'risk' => $cr->risk, 'changed_by' => $cr->requester_id, 'reason' => $cr->reason]);
            $cr->update(['status' => 'published', 'published_at' => now()]);
            $this->audit->log('settings.change_published', $cr);
            return $cr;
        });
    }
}
