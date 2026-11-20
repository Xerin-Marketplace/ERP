<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Routing\Controller;

class ModuleManagerController extends Controller
{
    /** Modules whose disable would break the platform or strand data flows. */
    private const PROTECTED = ['Core', 'SystemAdministration', 'Identity'];

    public function __construct(private AuditService $audit) {}

    public function index()
    {
        $modules = collect(\Module::all())->map(function ($m) {
            return [
                'name' => $m->getName(),
                'alias' => $m->getLowerName(),
                'description' => $m->get('description') ?: 'ERP module',
                'enabled' => $m->isEnabled(),
                'protected' => in_array($m->getName(), self::PROTECTED, true),
                'path' => $m->getPath(),
            ];
        })->sortBy('name')->values();

        return view('systemadministration::modules', ['modules' => $modules]);
    }

    public function toggle(string $name)
    {
        $module = \Module::find($name);
        abort_unless($module, 404);

        if (in_array($module->getName(), self::PROTECTED, true)) {
            return back()->with('error', "The {$module->getName()} module is protected and cannot be disabled.");
        }

        if ($module->isEnabled()) {
            $module->disable();
            $action = 'module.disabled';
        } else {
            $module->enable();
            $action = 'module.enabled';
        }

        $this->audit->log($action, null, null, ['module' => $module->getName()]);

        return back()->with('success', "Module {$module->getName()} ".($module->isEnabled() ? 'enabled' : 'disabled').'. Data was not touched.');
    }
}
