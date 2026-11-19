<?php

namespace Modules\HR\Services;

use App\Models\User;
use App\Services\AuditService;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\Employee;
use Modules\HR\Models\HRDocument;

class HRDocumentService
{
    public function __construct(
        private AuditService $audit,
        private HRNumberService $numbers,
        private FileService $files,
    ) {}

    public function store(Employee $employee, UploadedFile $file, array $data, User $actor): HRDocument
    {
        abort_unless($actor->can('hr.employee.manage') || $actor->can('hr.dashboard.view'), 403);

        return DB::transaction(function () use ($employee, $file, $data, $actor) {
            $managed = $this->files->store($file, 'hr-documents');

            $doc = new HRDocument([
                'employee_id' => $employee->id,
                'category' => $data['category'],
                'title' => $data['title'],
                'file_id' => $managed->id,
                'expires_at' => $data['expires_at'] ?? null,
                'confidential' => $data['confidential'] ?? false,
                'supersedes_id' => $data['supersedes_id'] ?? null,
            ]);
            if ($doc->supersedes_id) {
                $doc->version = (HRDocument::find($doc->supersedes_id)?->version ?? 0) + 1;
            }
            $doc->reference = $this->numbers->for('document');
            $doc->save();
            $this->audit->log('hr.document_uploaded', $doc, new: ['category' => $doc->category, 'version' => $doc->version]);
            return $doc;
        });
    }

    /** Confidential docs need hr.employee.manage; others need view access or ownership. */
    public function canView(User $viewer, HRDocument $doc): bool
    {
        if ($doc->confidential) {
            return $viewer->can('hr.employee.manage') || $viewer->can('hr.dashboard.view');
        }
        if ($viewer->can('hr.employee.view') || $viewer->can('hr.dashboard.view')) return true;
        return $doc->employee?->user_id === $viewer->id;
    }

    public function query(User $viewer)
    {
        $q = HRDocument::with(['employee.user', 'file'])->latest('id');
        if ($viewer->can('hr.employee.manage') || $viewer->can('hr.dashboard.view')) return $q;
        if ($viewer->can('hr.employee.view')) return $q->where('confidential', false);
        return $q->where('employee_id', $viewer->employee?->id ?? -1)->where('confidential', false);
    }
}
