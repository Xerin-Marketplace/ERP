<?php

namespace Modules\SystemAdministration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SystemAdministration\Models\DataImport;
use Modules\SystemAdministration\Services\DataImportService;

class DataImportController extends Controller
{
    public function __construct(private DataImportService $svc) {}

    public function index()
    {
        return view('systemadmin::import.index', [
            'imports' => DataImport::with('user:id,name')->latest()->paginate(20),
            'entities' => array_keys(DataImportService::ENTITIES),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'entity' => 'required|in:customers,suppliers',
            'file' => 'required|file|max:20480',
        ]);
        $import = $this->svc->stage($d['entity'], $r->file('file'), $r->user()->id);
        return redirect()->route('systemadmin.import.show', $import);
    }

    public function show(DataImport $import)
    {
        $headers = $this->svc->headers($import);
        return view('systemadmin::import.show', [
            'import' => $import,
            'headers' => $headers,
            'fields' => array_keys(DataImportService::ENTITIES[$import->entity]),
            'suggested' => $import->mapping ?? $this->svc->suggestMapping($import->entity, $headers),
        ]);
    }

    public function preview(Request $r, DataImport $import)
    {
        $d = $r->validate(['mapping' => 'required|array', 'mapping.*' => 'nullable|string|max:60']);
        $result = $this->svc->preview($import, $d['mapping']);
        return view('systemadmin::import.preview', [
            'import' => $import->fresh(),
            'rows' => $result['rows'],
            'errors' => $result['errors'],
        ]);
    }

    public function commit(DataImport $import)
    {
        $result = $this->svc->commit($import);
        return redirect()->route('systemadmin.import.show', $import)
            ->with('success', "Imported {$result['imported']} rows ({$result['skipped']} skipped).");
    }

    public function alias(Request $r)
    {
        $d = $r->validate([
            'entity' => 'required|in:customers,suppliers',
            'field' => 'required|string|max:40',
            'alias' => 'required|string|max:200',
            'canonical' => 'required|string|max:200',
        ]);
        $this->svc->registerAlias($d['entity'], $d['field'], $d['alias'], $d['canonical']);
        return back()->with('success', 'Alias saved.');
    }
}
