<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Employee\Models\DocumentCategory;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeDocument;
use Modules\Employee\Services\DocumentService;
use Modules\Employee\Services\EmployeeService;

class DocumentController extends Controller
{
    public function __construct(private DocumentService $docs, private EmployeeService $employees) {}

    public function index(Request $request, Employee $employee)
    {
        $this->authorizeView($request, $employee);
        $employee->load(['documents.category', 'documents.uploader:id,name']);

        return view('employee::documents.index', [
            'employee' => $employee,
            'categories' => DocumentCategory::where('is_active', true)->orderBy('name')->get(),
            'canUpload' => $request->user()->canAny(['employee.document.upload']) || $request->user()->employee?->id === $employee->id,
            'canDelete' => $request->user()->can('employee.document.delete'),
        ]);
    }

    public function store(Request $request, Employee $employee)
    {
        $user = $request->user();
        $isSelf = $user->employee?->id === $employee->id;
        abort_unless($isSelf || $user->can('employee.document.upload'), 403);

        $data = $request->validate([
            'category_id' => 'required|exists:employee_document_categories,id',
            'title' => 'required|string|max:255',
            'expires_at' => 'nullable|date',
            'file' => 'required|file|max:10240',
        ]);

        $this->docs->upload($employee, $data, $request->file('file'), $user->id);

        return back()->with('success', 'Document uploaded.');
    }

    public function download(Request $request, EmployeeDocument $document)
    {
        $this->authorizeView($request, $document->employee);

        return $this->docs->download($document);
    }

    public function destroy(Request $request, EmployeeDocument $document)
    {
        abort_unless($request->user()->can('employee.document.delete'), 403);
        $this->authorizeView($request, $document->employee);
        $this->docs->delete($document, $request->user()->id);

        return back()->with('success', 'Document removed.');
    }

    private function authorizeView(Request $request, Employee $employee): void
    {
        $user = $request->user();
        $isSelf = $user->employee?->id === $employee->id;
        abort_unless(
            ($isSelf && $user->can('employee.profile.update_own')) || $user->can('employee.document.view') || $this->employees->canView($user, $employee),
            403
        );
    }
}
