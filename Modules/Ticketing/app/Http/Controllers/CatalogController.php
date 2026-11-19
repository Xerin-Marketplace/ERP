<?php

namespace Modules\Ticketing\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Ticketing\Models\ServiceCatalogItem;
use Modules\Ticketing\Models\TicketCategory;
use Modules\Ticketing\Models\TicketType;
use Modules\Ticketing\Services\TicketService;

class CatalogController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index()
    {
        return view('ticketing::catalog.index', [
            'services' => ServiceCatalogItem::active()->with('category', 'formFields')->orderBy('name')->get(),
        ]);
    }

    public function request(ServiceCatalogItem $service)
    {
        abort_unless($service->is_active, 404);
        $service->load('formFields');

        return view('ticketing::catalog.request', compact('service'));
    }

    public function submit(Request $request, ServiceCatalogItem $service)
    {
        $user = $request->user();
        abort_unless($user->can('ticket.create'), 403);
        $service->load('formFields');

        $rules = ['subject' => 'required|string|max:255', 'description' => 'required|string', 'attachments.*' => 'file|max:10240'];
        foreach ($service->formFields->where('required', true) as $f) {
            $rules["custom.{$f->key}"] = 'required';
        }
        $data = $request->validate($rules);
        $data['custom_fields'] = $request->input('custom', []);

        $ticket = $this->tickets->createFromService($service, $data, $user);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $managed = app(\App\Services\FileService::class)->store($file, "tickets/{$ticket->id}", visibility: 'private');
                $ticket->attachments()->create(['managed_file_id' => $managed->id, 'uploaded_by' => $user->id]);
            }
        }

        return redirect()->route('ticketing.show', $ticket)->with('success', "Request submitted as {$ticket->ticket_number}.");
    }

    /* Admin management */
    public function manage(Request $request)
    {
        abort_unless($request->user()->can('service_catalog.manage'), 403);

        return view('ticketing::catalog.manage', [
            'services' => ServiceCatalogItem::with('type', 'category', 'defaultTeam')->orderBy('name')->get(),
            'types' => TicketType::active()->get(),
            'categories' => TicketCategory::active()->get(),
            'teams' => \Modules\Ticketing\Models\TicketTeam::active()->get(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('service_catalog.manage'), 403);
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:service_catalog_items,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ticket_type_id' => 'nullable|exists:ticket_types,id',
            'category_id' => 'nullable|exists:ticket_categories,id',
            'default_team_id' => 'nullable|exists:ticket_teams,id',
            'priority' => 'nullable|in:'.implode(',', \Modules\Ticketing\Models\Ticket::PRIORITIES),
            'requires_attachment' => 'nullable|boolean',
            'requires_approval' => 'nullable|boolean',
            'approval_chain' => 'nullable|string', // comma-separated roles
            'expected_hours' => 'nullable|integer|min:0',
        ]);
        $data['requires_attachment'] = $request->boolean('requires_attachment');
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['approval_chain'] = $request->filled('approval_chain')
            ? array_map('trim', explode(',', $request->approval_chain)) : null;

        ServiceCatalogItem::create($data);

        return back()->with('success', 'Service added to catalog.');
    }
}
