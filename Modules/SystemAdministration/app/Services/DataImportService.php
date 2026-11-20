<?php

namespace Modules\SystemAdministration\Services;

use App\Models\ManagedFile;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Commercial\Models\Customer;
use Modules\Commercial\Services\SequenceService;
use Modules\Finance\Models\Supplier;
use Modules\SystemAdministration\Models\DataImport;
use Modules\SystemAdministration\Models\DataImportAlias;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Master-file migration (spec): staged Excel/CSV import with column
 * mapping, spelling-variant normalization (data_import_aliases),
 * dry-run preview with per-row errors, then atomic commit.
 */
class DataImportService
{
    /** entity => [field => [required?, aliases-of-known-headers]] */
    public const ENTITIES = [
        'customers' => [
            'name' => ['required' => true],
            'type' => [], 'trading_name' => [], 'category' => [],
            'email' => [], 'phone' => [], 'whatsapp' => [],
            'tin' => [], 'address' => [], 'city' => [], 'country' => [],
            'credit_limit' => [], 'contact_person' => [], 'notes' => [],
        ],
        'suppliers' => [
            'name' => ['required' => true],
            'email' => [], 'phone' => [], 'tin' => [], 'address' => [],
            'payment_terms_days' => [],
        ],
    ];

    public function __construct(
        private AuditService $audit,
        private SequenceService $seq,
    ) {}

    // ── Parsing ─────────────────────────────────────────
    /** Store the upload + stage an import record. */
    public function stage(string $entity, $file, int $userId): DataImport
    {
        abort_unless(isset(self::ENTITIES[$entity]), 422, 'Unsupported entity.');
        abort_unless(in_array(strtolower($file->getClientOriginalExtension()),
            ['csv', 'xlsx', 'xls'], true), 422, 'Upload a .csv, .xlsx or .xls file.');

        $path = $file->store('imports', 'local');
        $managed = ManagedFile::create([
            'user_id' => $userId, 'disk' => 'local', 'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(), 'size' => $file->getSize(),
            'visibility' => 'private',
        ]);
        return DataImport::create([
            'entity' => $entity, 'file_id' => $managed->id,
            'original_name' => $file->getClientOriginalName(),
            'user_id' => $userId,
        ]);
    }

    /** Read rows as assoc arrays keyed by raw header text. */
    public function rows(DataImport $import): array
    {
        $file = $import->file;
        abort_unless(Storage::disk($file->disk)->exists($file->path), 404, 'Import file missing.');
        $full = Storage::disk($file->disk)->path($file->path);

        $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            $fh = fopen($full, 'r');
            $headers = fgetcsv($fh);
            $rows = [];
            while (($line = fgetcsv($fh)) !== false) {
                if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) continue;
                $rows[] = array_combine($headers, array_pad($line, count($headers), null));
            }
            fclose($fh);
            return $rows;
        }

        $sheet = IOFactory::load($full)->getActiveSheet();
        $grid = $sheet->toArray(null, true, true, false);
        $headers = array_map(fn ($h) => trim((string) $h), $grid[0] ?? []);
        $rows = [];
        foreach (array_slice($grid, 1) as $line) {
            if (count(array_filter($line, fn ($v) => trim((string) $v) !== '')) === 0) continue;
            $rows[] = array_combine($headers, array_pad($line, count($headers), null));
        }
        return $rows;
    }

    public function headers(DataImport $import): array
    {
        $rows = $this->rows($import);
        return $rows ? array_keys($rows[0]) : [];
    }

    /** Best-guess column→field mapping from header names. */
    public function suggestMapping(string $entity, array $headers): array
    {
        $map = [];
        foreach ($headers as $h) {
            $n = preg_replace('/[^a-z0-9]+/', '_', strtolower($h));
            $field = match (true) {
                str_contains($n, 'name') && ! str_contains($n, 'trading') && ! str_contains($n, 'contact') => 'name',
                str_contains($n, 'trading') => 'trading_name',
                str_contains($n, 'email') || str_contains($n, 'mail') => 'email',
                str_contains($n, 'phone') || str_contains($n, 'tel') || str_contains($n, 'mobile') => 'phone',
                str_contains($n, 'whatsapp') => 'whatsapp',
                str_contains($n, 'tin') || str_contains($n, 'tax') => 'tin',
                str_contains($n, 'addr') => 'address',
                str_contains($n, 'city') || str_contains($n, 'town') => 'city',
                str_contains($n, 'countr') => 'country',
                str_contains($n, 'credit') => 'credit_limit',
                str_contains($n, 'categor') || str_contains($n, 'group') => 'category',
                str_contains($n, 'type') => 'type',
                str_contains($n, 'contact') => 'contact_person',
                str_contains($n, 'term') => 'payment_terms_days',
                str_contains($n, 'note') || str_contains($n, 'remark') => 'notes',
                default => null,
            };
            if ($field && isset(self::ENTITIES[$entity][$field])) $map[$h] = $field;
        }
        return $map;
    }

    // ── Normalization ───────────────────────────────────
    /** Resolve a cell value through the alias table (case-insensitive). */
    public function normalize(string $entity, string $field, ?string $raw): ?string
    {
        if ($raw === null) return null;
        $v = trim($raw);
        if ($v === '') return null;
        $alias = DataImportAlias::where('entity', $entity)->where('field', $field)
            ->where('alias', mb_strtolower($v))->value('canonical');
        return $alias ?? $v;
    }

    public function registerAlias(string $entity, string $field, string $alias, string $canonical): DataImportAlias
    {
        return DataImportAlias::updateOrCreate(
            ['entity' => $entity, 'field' => $field, 'alias' => mb_strtolower(trim($alias))],
            ['canonical' => trim($canonical)]);
    }

    // ── Preview / commit ────────────────────────────────
    /**
     * Dry-run: apply mapping + aliases, validate rows, detect duplicates.
     * Returns ['rows' => [...], 'errors' => [{row,message}]].
     */
    public function preview(DataImport $import, array $mapping): array
    {
        abort_unless(isset(self::ENTITIES[$import->entity]), 422);
        abort_if(! in_array('name', $mapping, true), 422, 'Map a column to [name].');

        $rows = $this->rows($import);
        $import->update(['mapping' => $mapping, 'total_rows' => count($rows)]);

        $out = []; $errors = []; $seenNames = [];
        $model = $this->modelClass($import->entity);
        $existingEmails = $model::whereNotNull('email')->pluck('email')->map(
            fn ($e) => mb_strtolower($e))->all();

        foreach ($rows as $i => $raw) {
            $data = [];
            foreach ($mapping as $col => $field) {
                if (! $field || ! isset(self::ENTITIES[$import->entity][$field])) continue;
                $data[$field] = $this->normalize($import->entity, $field, $raw[$col] ?? null);
            }
            $rowErrors = [];

            if (empty($data['name'])) $rowErrors[] = 'name is required';
            if (! empty($data['email'])) {
                if (! filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "invalid email [{$data['email']}]";
                } elseif (in_array(mb_strtolower($data['email']), $existingEmails, true)) {
                    $rowErrors[] = 'email already exists';
                }
            }
            if (! empty($data['name'])) {
                $key = mb_strtolower($data['name']);
                if (isset($seenNames[$key])) $rowErrors[] = "duplicate of row {$seenNames[$key]}";
                $seenNames[$key] = $i + 2; // header is row 1
                if ($model::where('name', $data['name'])->exists()) $rowErrors[] = 'name already exists';
            }
            if (isset($data['credit_limit']) && ! is_numeric($data['credit_limit'])) {
                $rowErrors[] = 'credit_limit not numeric';
            }
            if (isset($data['payment_terms_days']) && ! is_numeric($data['payment_terms_days'])) {
                $rowErrors[] = 'payment_terms_days not numeric';
            }

            foreach ($rowErrors as $e) $errors[] = ['row' => $i + 2, 'message' => $e];
            $out[] = ['line' => $i + 2, 'data' => $data, 'ok' => $rowErrors === []];
        }

        $import->update(['status' => 'previewed', 'error_count' => count($errors), 'errors' => $errors]);
        return ['rows' => $out, 'errors' => $errors];
    }

    /** Commit only the valid previewed rows — atomic. */
    public function commit(DataImport $import): array
    {
        abort_unless($import->status === 'previewed', 422, 'Run preview first.');
        $check = $this->preview($import, $import->mapping ?? []);
        $valid = array_filter($check['rows'], fn ($r) => $r['ok']);

        $created = 0;
        DB::transaction(function () use ($import, $valid, &$created) {
            $model = $this->modelClass($import->entity);
            foreach ($valid as $row) {
                $data = $row['data'];
                $data['company_id'] = $this->companyId();
                if ($import->entity === 'customers') {
                    $data['status'] = 'active';
                    $data['source'] = 'import';
                    $rec = new Customer($data);
                    $rec->forceFill(['code' => $this->seq->customer()])->save();
                } else {
                    $rec = $model::create($data + ['code' => $this->nextSupplierCode()]);
                }
                $created++;
            }
        });

        $import->update(['status' => 'imported', 'imported_count' => $created,
            'skipped_count' => $import->total_rows - $created]);
        $this->audit->log('sysadmin.data_import.committed', $import,
            new: ['entity' => $import->entity, 'imported' => $created]);
        return ['imported' => $created, 'skipped' => $import->total_rows - $created];
    }

    private function modelClass(string $entity): string
    {
        return $entity === 'customers' ? Customer::class : Supplier::class;
    }

    private function companyId(): int
    {
        return (int) \Modules\Organization\Models\Company::value('id');
    }

    private function nextSupplierCode(): string
    {
        $max = Supplier::where('code', 'like', 'XER-SUP-%')->lockForUpdate()->max('code');
        return sprintf('XER-SUP-%06d', $max ? ((int) substr($max, -6)) + 1 : 1);
    }
}
