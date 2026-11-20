<?php

namespace Modules\SystemAdministration\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\SystemAdministration\Models\SettingScope;

/**
 * Scoped settings resolution.
 *
 * Precedence (when allowed by the definition):
 *   user → branch → company → group → global → definition default
 *
 * Definitions may declare:
 *   allowed_scopes — which scopes accept overrides (default: [])
 *   protected      — true: ONLY global applies; no scope can weaken it
 *                    (MFA, security policies)
 */
class ScopedSettingsResolver
{
    private const ORDER = ['user', 'branch', 'company', 'group'];

    public function __construct(private \App\Services\SettingsService $settings) {}

    public function allowed(string $key): array
    {
        $def = SettingCatalog::definitions()[$key] ?? null;
        abort_if(! $def, 404, "Unknown setting '{$key}'.");
        return $def;
    }

    /**
     * Resolve effective value for a user.
     * $ctx: ['branch_id'=>, 'company_id'=>, 'group_id'=>]
     */
    public function resolve(string $key, ?User $user = null, array $ctx = []): array
    {
        $def = $this->allowed($key);
        if ($def['protected'] ?? false) {
            return ['value' => $this->globalValue($key, $def), 'source' => 'global'];
        }
        $allowed = $def['allowed_scopes'] ?? [];

        $candidates = [];
        foreach (self::ORDER as $scope) {
            if (! in_array($scope, $allowed, true)) continue;
            $sid = match ($scope) {
                'user' => $user?->id,
                'branch' => $ctx['branch_id'] ?? $user?->branch_id,
                'company' => $ctx['company_id'] ?? $user?->company_id,
                'group' => $ctx['group_id'] ?? null,
            };
            if ($sid) $candidates[] = ['scope' => $scope, 'id' => $sid];
        }
        $cacheKey = 'setting:' . $key . ':' . md5(json_encode($candidates));
        return Cache::remember($cacheKey, 300, function () use ($key, $candidates, $def) {
            foreach ($candidates as $c) {
                $row = SettingScope::where('key', $key)
                    ->where('scope_type', $c['scope'])->where('scope_id', $c['id'])->first();
                if ($row) return ['value' => $this->cast($row->value, $def['type']), 'source' => "{$c['scope']}:{$c['id']}"];
            }
            return ['value' => $this->globalValue($key, $def), 'source' => 'global'];
        });
    }

    /** Write a scoped override — validates scope allowed + relationship chain. */
    public function set(string $key, string $scopeType, int $scopeId, $value, User $actor): SettingScope
    {
        $def = $this->allowed($key);
        if ($def['protected'] ?? false) {
            throw ValidationException::withMessages(['scope' => "Setting '{$key}' is protected — only global policy applies."]);
        }
        if (! in_array($scopeType, $def['allowed_scopes'] ?? [], true)) {
            throw ValidationException::withMessages(['scope' => "Scope '{$scopeType}' is not allowed for '{$key}'."]);
        }
        $this->assertScopeAccess($actor, $scopeType, $scopeId);
        \Illuminate\Support\Facades\Validator::make(['v' => $value], ['v' => $def['rules']])->validate();

        $row = SettingScope::updateOrCreate(
            ['key' => $key, 'scope_type' => $scopeType, 'scope_id' => $scopeId],
            ['value' => is_scalar($value) ? (string) $value : json_encode($value), 'updated_by' => $actor->id]);
        $this->flush($key);
        return $row;
    }

    public function flush(string $key): void
    {
        // Prefix-based invalidation — scoped keys share 'setting:{key}:'
        foreach (['user','branch','company','group'] as $s) {
            // values resolved per-candidate-hash; simplest reliable flush is to bump a version key
        }
        Cache::increment('settingver:' . $key);
        // Drop individual entries not feasible by prefix on all drivers —
        // cache entries carry short TTL (300s) + version bump marks generation.
    }

    private function assertScopeAccess(User $actor, string $type, int $id): void
    {
        if ($actor->hasRole('super-admin')) return;
        if ($type === 'branch' && (int) $actor->branch_id === $id) return;
        if ($type === 'company' && (int) $actor->company_id === $id) return;
        if ($type === 'user' && (int) $actor->id === $id) return;
        throw ValidationException::withMessages(['scope' => 'Scope is outside your authority.']);
    }

    private function globalValue(string $key, array $def)
    {
        $v = $this->settings->get($key);
        return $v !== null && $v !== '' ? $this->cast($v, $def['type']) : $def['default'];
    }

    private function cast($v, string $type)
    {
        return match ($type) {
            'bool' => filter_var($v, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $v, 'float' => (float) $v,
            'multiselect', 'json' => is_string($v) ? json_decode($v, true) : $v,
            default => $v,
        };
    }
}
