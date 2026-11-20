<?php

namespace Modules\Arrival\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Arrival\Models\ReleaseApproval;
use Modules\Arrival\Services\ReleaseService;
use Modules\Arrival\Services\StorageService;
use Modules\Cargo\Models\CargoPackage;
use Modules\Finance\Models\CustomerInvoice;

class ReleaseController extends Controller
{
    public function __construct(
        private ReleaseService $releases,
        private StorageService $storage,
    ) {}

    public function index(Request $r)
    {
        return view('arrival::releases.index', [
            'approvals' => ReleaseApproval::with('package', 'invoice', 'decider')
                ->when($r->status, fn ($q) => $q->where('status', $r->status))
                ->latest()->paginate(25),
            'pendingCount' => ReleaseApproval::where('status', 'pending')->count(),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'package_id' => 'required|exists:cargo_packages,id',
            'invoice_id' => 'nullable|exists:customer_invoices,id',
        ]);
        $package = CargoPackage::findOrFail($data['package_id']);
        $a = $this->releases->request($package, $data['invoice_id'] ?? null,
            $package->branch_id, $r->user()->id);
        return back()->with('success', "Release request {$a->reference} created.");
    }

    public function decide(Request $r, ReleaseApproval $approval)
    {
        $data = $r->validate([
            'decision' => 'required|in:approved,on_hold,rejected',
            'reason' => 'nullable|string|max:1000',
        ]);
        $a = $this->releases->decide($approval, $r->user()->id, $data['decision'], $data['reason'] ?? null);
        return back()->with('success',
            $a->status === 'approved' ? "Approved — release code: {$a->release_code}" : ucfirst($a->status).'.');
    }

    public function verify(Request $r)
    {
        $data = $r->validate([
            'package_id' => 'required|exists:cargo_packages,id',
            'code' => 'required|string|size:6',
        ]);
        $ok = $this->releases->verifyCode(CargoPackage::findOrFail($data['package_id']), $data['code']);
        return back()->with($ok ? 'success' : 'error',
            $ok ? 'Release code verified — hand over cargo.' : 'Invalid or used release code.');
    }

    public function storage(CargoPackage $package)
    {
        $received = $package->receiving?->received_at ?? $package->created_at;
        return response()->json($this->storage->charge($package, $received));
    }
}
