<?php

namespace Modules\Core\Http\Controllers;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Nwidart\Modules\Facades\Module;

class CoreController extends ApiController
{
    /**
     * GET /api/v1/core/status — module engine status (sanctum-protected).
     */
    public function status(): JsonResponse
    {
        $modules = collect(Module::all())->map(fn ($module) => [
            'name' => $module->getName(),
            'alias' => $module->getLowerName(),
            'enabled' => $module->isEnabled(),
            'priority' => $module->getPriority(),
        ])->values();

        return $this->ok([
            'module_engine' => 'nwidart/laravel-modules',
            'modules' => $modules,
            'count' => $modules->count(),
        ], 'Module engine status');
    }
}
