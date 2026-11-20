<?php

namespace Modules\SystemAdministration\Console\Commands;

use Illuminate\Console\Command;
use Modules\SystemAdministration\Services\CommandRegistry;

/**
 * Server-side CLI access to the Command Center catalog.
 *   php artisan xerin:command system.health.check
 *   php artisan xerin:command system.info --format=json
 *   php artisan xerin:command --list
 */
class XerinCommand extends Command
{
    protected $signature = 'xerin:command {id? : command id from the catalog}
                            {--params= : JSON params}
                            {--format=text : text|json}
                            {--list : list registered commands}';

    protected $description = 'Execute a registered XERIN administrative command';

    public function handle(): int
    {
        if ($this->option('list') || ! $this->argument('id')) {
            $rows = collect(CommandRegistry::definitions())->map(fn ($d, $id) =>
                [$id, $d['category'], $d['risk'], $d['description']])->values()->all();
            $this->table(['ID', 'Category', 'Risk', 'Description'], $rows);
            return 0;
        }

        $def = CommandRegistry::find($id = $this->argument('id'));
        if (! $def) {
            $this->error("Unknown command '{$id}'. Run with --list.");
            return 2;
        }

        $params = $this->option('params') ? (json_decode($this->option('params'), true) ?? []) : [];
        $t = microtime(true);
        try {
            $out = ($def['handler'])($params);
        } catch (\Throwable $e) {
            $this->error('FAILED: ' . $e->getMessage());
            return 1;
        }

        if ($this->option('format') === 'json') {
            $this->line(json_encode(['command' => $id, 'status' => 'ok',
                'ms' => (int)((microtime(true)-$t)*1000), 'result' => $out], JSON_PRETTY_PRINT));
        } else {
            $this->info("{$id} — OK (" . (int)((microtime(true)-$t)*1000) . "ms)");
            $this->line(is_string($out) ? $out : json_encode($out, JSON_PRETTY_PRINT));
        }
        return 0;
    }
}
