<?php

namespace Modules\SystemAdministration\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class BackupController extends Controller
{
    public function index()
    {
        // Honest status: there is no backup subsystem configured in this
        // deployment, so the page reports that rather than fabricating runs.
        return view('systemadministration::backups', [
            'configured' => false,
            'records' => collect(),
        ]);
    }
}
