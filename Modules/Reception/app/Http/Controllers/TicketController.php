<?php

namespace Modules\Reception\Http\Controllers;

use App\Models\ManagedFile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Employee\Models\Employee;
use Modules\Reception\Services\ReceptionService;
use Modules\Ticketing\Models\Ticket;
use Modules\Ticketing\Models\TicketCategory;
use Modules\Ticketing\Services\TicketService;

class TicketController extends Controller
{
    public function index(ReceptionService $svc)
    {
        $branchId = $svc->scopeBranch(auth()->user());

        return view('reception::tickets.index', [
            'tickets' => $svc->branchTickets($branchId, request()->only(['status', 'priority', 'q'])),
            'statuses' => Ticket::STATUSES,
        ]);
    }

    public function create(ReceptionService $svc)
    {
        $branchId = $svc->scopeBranch(auth()->user());

        return view('reception::tickets.create', [
            'categories' => TicketCategory::orderBy('name')->get(['id', 'name']),
            'employees' => Employee::with('personal:id,employee_id,first_name,last_name')
                ->inBranch($branchId)->where('status', 'active')->orderBy('employee_number')->get(['id', 'employee_number']),
            'priorities' => ['low', 'normal', 'high', 'urgent'],
        ]);
    }

    public function store(Request $request, TicketService $svc, ReceptionService $rsvc)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'category_id' => ['nullable', 'exists:ticket_categories,id'],
            'subject' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'location' => ['nullable', 'string', 'max:200'],
            'attachments.*' => ['file', 'max:20480', 'mimetypes:image/png,image/jpeg,application/pdf,video/mp4,video/quicktime'],
        ]);

        $user = $request->user();
        $branchId = $rsvc->scopeBranch($user);
        $employee = Employee::findOrFail($data['employee_id']);

        // Branch enforcement: receptionist can only open tickets for own-branch employees.
        if ($branchId && $employee->branch_id !== $branchId) {
            abort(403, 'Employee belongs to another branch.');
        }

        $ticket = $svc->create([
            'subject' => $data['subject'],
            'description' => $data['description'].(! empty($data['location']) ? "\n\nLocation: {$data['location']}" : ''),
            'category_id' => $data['category_id'] ?? null,
            'priority' => $data['priority'],
            'on_behalf_employee_id' => $employee->id,
            'branch_code' => $employee->branch?->code,
        ], $user);

        foreach ((array) $request->file('attachments', []) as $file) {
            $this->storeAttachment($ticket, $file, $user->id);
        }

        return redirect()->route('reception.tickets.show', $ticket)->with('success', "Ticket {$ticket->ticket_number} created.");
    }

    public function show(Ticket $ticket, ReceptionService $svc)
    {
        $this->authorizeBranch($ticket, $svc);
        $ticket->load(['category:id,name', 'assignedAgent:id,name', 'requesterEmployee.personal', 'publicComments.author:id,name', 'statusHistories', 'attachments.file', 'attachments.uploader:id,name']);

        return view('reception::tickets.show', compact('ticket'));
    }

    public function comment(Request $request, Ticket $ticket, ReceptionService $svc)
    {
        $this->authorizeBranch($ticket, $svc);
        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        DB::table('ticket_comments')->insert([
            'ticket_id' => $ticket->id,
            'author_id' => $request->user()->id,
            'body' => $data['body'],
            'is_internal' => false,
            'kind' => 'comment',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return back()->with('success', 'Comment added.');
    }

    public function attach(Request $request, Ticket $ticket, ReceptionService $svc)
    {
        $this->authorizeBranch($ticket, $svc);
        $request->validate(['file' => ['required', 'file', 'max:20480', 'mimetypes:image/png,image/jpeg,application/pdf,video/mp4,video/quicktime']]);

        $this->storeAttachment($ticket, $request->file('file'), $request->user()->id);

        return back()->with('success', 'Attachment uploaded.');
    }

    private function authorizeBranch(Ticket $ticket, ReceptionService $svc): void
    {
        $branchId = $svc->scopeBranch(auth()->user());
        abort_if($branchId && $ticket->requester_branch_id && $ticket->requester_branch_id !== $branchId, 403);
    }

    private function storeAttachment(Ticket $ticket, $file, int $userId): void
    {
        $path = $file->store("tickets/{$ticket->id}", 'local');
        $managed = ManagedFile::create([
            'user_id' => $userId,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'visibility' => 'private',
        ]);
        DB::table('ticket_attachments')->insert([
            'ticket_id' => $ticket->id,
            'managed_file_id' => $managed->id,
            'uploaded_by' => $userId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
