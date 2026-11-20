<?php

namespace Modules\SystemAdministration\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Models\Announcement;

class AnnouncementController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function index()
    {
        return view('systemadministration::announcements', [
            'announcements' => Announcement::with('createdBy:id,name')->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:2000',
            'level' => 'required|in:info,warning,danger',
            'audience' => 'required|in:all,admins',
            'is_active' => 'nullable|boolean',
            'requires_ack' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['requires_ack'] = (bool) ($data['requires_ack'] ?? false);
        $data['created_by'] = $request->user()->id;

        $a = Announcement::create($data);
        $this->audit->log('announcement.created', $a, null, ['title' => $a->title]);

        return back()->with('success', 'Announcement created.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:2000',
            'level' => 'required|in:info,warning,danger',
            'audience' => 'required|in:all,admins',
            'is_active' => 'nullable|boolean',
            'requires_ack' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);
        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $data['requires_ack'] = (bool) ($data['requires_ack'] ?? false);

        $announcement->update($data);
        $this->audit->log('announcement.updated', $announcement, null, ['title' => $announcement->title]);

        return back()->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        $this->audit->log('announcement.deleted', $announcement);

        return back()->with('success', 'Announcement deleted.');
    }
}
