<?php
namespace Modules\WorkCenter\Services;

use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Modules\WorkCenter\Models\{Document, DocumentVersion, DocumentShare};

/** Central DMS — one repo for all ERP documents via morphTo attachable. */
class DocumentService
{
    public function __construct(private AuditService $audit,
        private ReferenceService $refs,
        private \App\Services\FileService $files) {}

    public function upload(array $d, UploadedFile $file, User $owner,
        ?\Illuminate\Database\Eloquent\Model $attachTo = null): Document
    {
        $mf = $this->files->store($file, 'documents');
        $doc = Document::create($d + [
            'ref' => $this->refs->next('XER-DOC', 'documents'),
            'owner_id' => $owner->id, 'file_id' => $mf->id,
            'company_id' => $owner->company_id ?? \Modules\Organization\Models\Company::value('id'),
            'attachable_type' => $attachTo?->getMorphClass(),
            'attachable_id' => $attachTo?->getKey(),
        ]);
        DocumentVersion::create(['document_id' => $doc->id, 'version' => 1,
            'file_id' => $mf->id, 'uploaded_by' => $owner->id]);
        $this->audit->log('document.uploaded', $doc);
        return $doc;
    }

    public function newVersion(Document $d, UploadedFile $file, User $actor, ?string $note = null): DocumentVersion
    {
        abort_if($d->status === 'archived', 422, 'Archived — restore first.');
        $mf = $this->files->store($file, 'documents');
        $v = (int) $d->versions()->max('version') + 1;
        $d->update(['version_no' => $v, 'file_id' => $mf->id]);
        $dv = DocumentVersion::create(['document_id' => $d->id, 'version' => $v,
            'file_id' => $mf->id, 'change_note' => $note, 'uploaded_by' => $actor->id]);
        $this->audit->log('document.new_version', $dv);
        return $dv;
    }

    public function share(Document $d, User $to, User $grantedBy, string $perm = 'view'): DocumentShare
    {
        return DocumentShare::updateOrCreate(['document_id' => $d->id, 'user_id' => $to->id],
            ['permission' => $perm, 'granted_by' => $grantedBy->id]);
    }

    public function canView(Document $d, User $u): bool
    {
        if ($u->hasRole('super-admin') || $d->owner_id === $u->id) return true;
        if ($d->shares()->where('user_id', $u->id)->exists()) return true;
        return match ($d->visibility) {
            'company' => $d->company_id === $u->company_id,
            'branch' => $d->branch_id === $u->branch_id,
            'department' => $d->department_id === $u->department_id,
            default => false,
        };
    }

    /** Days until expiry — negative = expired. */
    public function expiryInDays(Document $d): ?int
    {
        return $d->expires_at ? (int) now()->startOfDay()->diffInDays($d->expires_at, false) : null;
    }
}
