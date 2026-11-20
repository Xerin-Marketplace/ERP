<?php

namespace Modules\SystemAdministration\Services;

use InvalidArgumentException;

/**
 * Central registry of executable administrative commands.
 * Definitions live in CommandCatalog; handlers are real PHP callables —
 * never user-supplied strings, never shell.
 */
class CommandRegistry
{
    /** @return array<string,array> */
    public static function definitions(): array
    {
        return CommandCatalog::definitions();
    }

    public static function find(string $id): ?array
    {
        return self::definitions()[$id] ?? null;
    }

    public static function mustFind(string $id): array
    {
        return self::find($id) ?? throw new InvalidArgumentException("Unknown command '{$id}'.");
    }

    /** Commands the user is authorized to see/execute (permission-scoped). */
    public static function forUser($user): array
    {
        return collect(self::definitions())
            ->filter(fn ($d) => ! isset($d['permission']) || $user->can($d['permission']))
            ->all();
    }
}
