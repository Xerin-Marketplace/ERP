<?php

namespace Modules\Organization\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Organization\Services\OrganizationService;

class OverviewController extends Controller
{
    public function __construct(private OrganizationService $org) {}

    public function index()
    {
        return view('organization::overview', ['stats' => $this->org->overview()]);
    }
}
