<?php

namespace Modules\Employee\Services;

use App\Services\AuditService;
use App\Services\FileService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Employee\Models\DocumentCategory;
use Modules\Employee\Models\Employee;
use Modules\Employee\Models\EmployeeDocument;

class DocumentService
{
    public function __construct(
        private FileService $files,
        private AuditService $audit,
        private EmployeeService $employees,
    ) {}

    public function upload(Employee $employee, array $data, UploadedFile $file, int $actorId): EmployeeDocument
    {
        $category = DocumentCategory::where('is_active', true)->findOrFail($data['category_id']);

        if ($category->requires_expiry && empty($data['expires_at'])) {
            throw ValidationException::withMessages(['expires_at' => 'This document category requires an expiry date.']);
        }

        return DB::transaction(function () use ($employee, $data, $file, $category, $actorId) {
            $managed = $this->files->store($file, "employees/{$employee->id}", visibility: 'private');

            // Auto-version: same employee + category + title bumps the version
            $version = ($employee->documents()
                ->where('category_id', $category->id)->where('title', $data['title'])
                ->max('version') ?? 0) + 1;

            $doc = $employee->documents()->create([
                'category_id' => $category->id,
                'managed_file_id' => $managed->id,
                'title' => $data['title'],
                'expires_at' => $data['expires_at'] ?? null,
                'version' => $version,
                'uploaded_by' => $actorId,
            ]);

            $this->employees->record($employee, 'document.uploaded', "Document '{$doc->title}' uploaded", [
                'category' => $category->name, 'version' => $version,
            ], $actorId);
            $this->audit->log('employee.document_uploaded', $doc, new: ['employee_id' => $employee->id, 'category' => $category->code]);

            return $doc;
        });
    }

    public function download(EmployeeDocument $document)
    {
        $this->audit->log('employee.document_downloaded', $document, metadata: ['employee_id' => $document->employee_id]);

        return $this->files->download($document->file);
    }

    public function delete(EmployeeDocument $document, int $actorId): void
    {
        DB::transaction(function () use ($document, $actorId) {
            $document->delete(); // soft delete — file preserved for audit
            $this->employees->record($document->employee, 'document.deleted', "Document '{$document->title}' removed", null, $actorId);
            $this->audit->log('employee.document_deleted', $document);
        });
    }
}
