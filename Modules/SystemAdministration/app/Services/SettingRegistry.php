<?php

namespace Modules\SystemAdministration\Services;

use InvalidArgumentException;

/**
 * Settings Registry — modules self-declare their configurable settings.
 *
 * A module registers definitions in its service provider boot():
 *
 *   SettingRegistry::register('cargo', [
 *       'cargo.auto_assign_shelf' => [
 *           'group' => 'Cargo', 'label' => 'Auto-assign shelf',
 *           'type' => 'bool', 'default' => true,
 *           'rules' => 'boolean', 'risk' => 'low',
 *       ],
 *   ]);
 *
 * Rules:
 *  - Keys MUST be prefixed `{module}.` — keeps ownership clear.
 *  - Module definitions extend the core SettingCatalog; they flow through
 *    the same typed validation, versioning, risk classification and audit.
 *  - No module may register keys in another module's namespace or
 *    redefine a locked core key.
 */
class SettingRegistry
{
    /** @var array<string, array<string, array>> module => key => def */
    private static array $registered = [];

    private const REQUIRED_KEYS = ['group', 'label', 'type', 'default', 'rules', 'risk'];

    public static function register(string $module, array $definitions): void
    {
        foreach ($definitions as $key => $def) {
            if (! str_starts_with($key, $module . '.')) {
                throw new InvalidArgumentException(
                    "Setting '{$key}' must be prefixed '{$module}.' — modules own their namespace.");
            }
            $missing = array_diff(self::REQUIRED_KEYS, array_keys($def));
            if ($missing) {
                throw new InvalidArgumentException(
                    "Setting '{$key}' missing keys: " . implode(',', $missing));
            }
            $def['module'] = $module;
            self::$registered[$module][$key] = $def;
        }
    }

    /** All module-registered definitions, merged key => def. */
    public static function definitions(): array
    {
        return collect(self::$registered)->flatMap(fn ($defs) => $defs)->all();
    }

    /** Metadata for the admin UI: which modules declared settings. */
    public static function modules(): array
    {
        return collect(self::$registered)
            ->map(fn ($defs, $m) => ['module' => $m, 'count' => count($defs)])
            ->values()->all();
    }

    public static function flush(): void
    {
        self::$registered = [];
    }
}
