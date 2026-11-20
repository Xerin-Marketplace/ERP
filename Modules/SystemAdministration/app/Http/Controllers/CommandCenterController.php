<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\SystemAdministration\Models\CommandApproval;
use Modules\SystemAdministration\Models\CommandExecution;
use Modules\SystemAdministration\Services\CommandExecutor;
use Modules\SystemAdministration\Services\CommandRegistry;

class CommandCenterController extends Controller
{
    public function __construct(private CommandExecutor $executor) {}

    public function index(Request $request)
    {
        abort_unless($request->user()->can('sysadmin.commands.view'), 403);
        $commands = CommandRegistry::forUser($request->user());
        return view('systemadministration::command-center', [
            'commands' => collect($commands)->groupBy('category'),
            'recent' => CommandExecution::with('user:id,name')->latest()->limit(10)->get(),
            'pending' => CommandExecution::where('status', 'pending_approval')->with('user:id,name')->get(),
            'stats' => [
                'total' => count($commands),
                'executed' => CommandExecution::whereIn('status', ['completed','failed'])->count(),
                'failed' => CommandExecution::where('status', 'failed')->count(),
            ],
        ]);
    }

    public function execute(string $id, Request $request)
    {
        abort_unless($request->user()->can('sysadmin.commands.execute'), 403);
        $def = CommandRegistry::mustFind($id);
        $params = $request->input('params', []);
        $exec = $this->executor->submit($id, $params, $request->user(), $request->input('reason'));
        return back()->with($exec->status === 'completed' ? 'success' : 'info',
            $exec->status === 'pending_approval'
                ? "Command '{$def['name']}' queued for approval."
                : "Executed: {$exec->status}")
            ->with('execution_id', $exec->id);
    }

    public function history(Request $request)
    {
        abort_unless($request->user()->can('sysadmin.commands.view'), 403);
        return view('systemadministration::command-history', [
            'executions' => CommandExecution::with('user:id,name')->latest()->paginate(25),
        ]);
    }

    public function approve(Request $request, CommandExecution $execution)
    {
        $this->executor->approve($execution, $request->user(), $request->input('comment'));
        return back()->with('success', 'Command approved and executed.');
    }

    public function reject(Request $request, CommandExecution $execution)
    {
        $this->executor->reject($execution, $request->user(), $request->input('comment'));
        return back()->with('success', 'Command rejected.');
    }
}
