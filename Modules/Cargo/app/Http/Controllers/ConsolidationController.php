<?php

namespace Modules\Cargo\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cargo\Models\CargoBox;
use Modules\Cargo\Models\CargoBooking;
use Modules\Cargo\Models\CargoPackage;
use Modules\Cargo\Models\CargoTrip;
use Modules\Cargo\Services\BookingService;
use Modules\Cargo\Services\ConsolidationService;
use Modules\Cargo\Services\TripService;
use Modules\Commercial\Models\Customer;
use Modules\Organization\Models\Branch;

class ConsolidationController extends Controller
{
    public function __construct(
        private ConsolidationService $consolidation,
        private TripService $trips,
        private BookingService $bookings,
    ) {}

    // ---- Boxes ----
    public function boxes()
    {
        return view('cargo::consolidation.boxes', [
            'boxes' => CargoBox::with('lines', 'trip')->latest()->paginate(25),
            'openCount' => CargoBox::where('status', 'open')->count(),
        ]);
    }

    public function boxStore(Request $r)
    {
        $data = $r->validate([
            'branch_id' => 'required|exists:branches,id',
            'target_weight' => 'nullable|numeric|min:1|max:200',
            'box_type' => 'nullable|string|max:40',
        ]);
        $box = $this->consolidation->createBox($data['branch_id'], $data, $r->user()->id);
        return redirect()->route('cargo.consolidation.boxes.show', $box)->with('success', "Box {$box->reference} created.");
    }

    public function boxShow(CargoBox $box)
    {
        $box->load('lines.package', 'trip');
        return view('cargo::consolidation.box-show', [
            'box' => $box,
            'linesWeight' => (float) $box->lines->sum('weight'),
            'packages' => CargoPackage::where('branch_id', $box->branch_id)
                ->where('unidentified', false)->whereNull('booking_id')
                ->limit(50)->get(['id', 'reference', 'tracking_no', 'gross_weight']),
        ]);
    }

    public function boxAddLine(Request $r, CargoBox $box)
    {
        $data = $r->validate([
            'package_id' => 'required|exists:cargo_packages,id',
            'weight' => 'nullable|numeric|min:0',
        ]);
        $this->consolidation->addPackage($box, CargoPackage::findOrFail($data['package_id']),
            $data['weight'] ?? null, $r->user()->id);
        return back()->with('success', 'Package added.');
    }

    public function boxClose(Request $r, CargoBox $box)
    {
        $data = $r->validate([
            'actual_weight' => 'required|numeric|min:0.1',
            'seal_number' => 'required|string|max:60',
        ]);
        $this->consolidation->close($box, $data['actual_weight'], $data['seal_number'], $r->user()->id);
        return back()->with('success', 'Box closed and sealed.');
    }

    public function boxAssign(Request $r, CargoBox $box)
    {
        $data = $r->validate(['trip_id' => 'required|exists:cargo_trips,id']);
        $this->consolidation->assignToTrip($box, CargoTrip::findOrFail($data['trip_id']), $r->user()->id);
        return back()->with('success', 'Box assigned to trip.');
    }

    /** Air-cargo leg: assign the sealed box onto a Shipment/AWB. */
    public function boxAssignShipment(Request $r, CargoBox $box)
    {
        $data = $r->validate(['shipment_id' => 'required|exists:shipments,id']);
        $this->consolidation->assignToShipment($box,
            \Modules\Shipment\Models\Shipment::findOrFail($data['shipment_id']), $r->user()->id);
        return back()->with('success', 'Box loaded onto shipment.');
    }

    // ---- Trips ----
    public function trips()
    {
        return view('cargo::consolidation.trips', [
            'trips' => CargoTrip::with('boxes')->latest('flight_date')->paginate(25),
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function tripStore(Request $r)
    {
        $data = $r->validate([
            'passenger_name' => 'required|string|max:255',
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id|different:origin_branch_id',
            'passenger_id_doc' => 'nullable|string|max:80',
            'passenger_phone' => 'nullable|string|max:30',
            'airline' => 'nullable|string|max:80',
            'flight_number' => 'nullable|string|max:20',
            'flight_date' => 'nullable|date',
            'baggage_allowance' => 'nullable|numeric|min:0',
            'passenger_fee' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
        ]);
        $trip = $this->trips->create($data, $r->user()->id);
        return redirect()->route('cargo.consolidation.trips.show', $trip)->with('success', "Trip {$trip->reference} created.");
    }

    public function tripShow(CargoTrip $trip)
    {
        $trip->load('boxes.lines');
        return view('cargo::consolidation.trip-show', [
            'trip' => $trip,
            'weight' => $this->trips->weightCheck($trip),
            'profit' => app(\Modules\Cargo\Services\ShipmentProfitService::class)->tripProfit($trip),
            'closedBoxes' => CargoBox::where('status', 'closed')->whereNull('trip_id')->get(),
        ]);
    }

    public function tripHandoverDxb(CargoTrip $trip, Request $r)
    {
        $this->trips->handoverDubai($trip, $r->user()->id);
        return back()->with('success', 'Dubai handover signed.');
    }

    public function tripDepart(CargoTrip $trip, Request $r)
    {
        $data = $r->validate(['evidence_file_id' => 'required|integer|min:1']);
        $this->trips->depart($trip, $data['evidence_file_id'], $r->user()->id);
        return back()->with('success', 'Trip departed.');
    }

    public function tripArrive(CargoTrip $trip)
    {
        $this->trips->arrive($trip);
        return back()->with('success', 'Trip arrived.');
    }

    public function tripHandoverTz(CargoTrip $trip, Request $r)
    {
        $this->trips->handoverTanzania($trip, $r->user()->id);
        return back()->with('success', 'Tanzania handover signed — trip reconciled.');
    }

    // ---- Bookings ----
    public function bookings(Request $r)
    {
        return view('cargo::consolidation.bookings', [
            'bookings' => CargoBooking::with('customer', 'branch')
                ->when($r->status, fn ($q) => $q->where('status', $r->status))
                ->latest()->paginate(25),
            'customers' => Customer::where('is_active', true)->orderBy('name')->limit(100)->get(['id', 'name', 'code']),
            'branches' => Branch::active()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function bookingStore(Request $r)
    {
        $data = $r->validate([
            'customer_id' => 'required|exists:customers,id',
            'branch_id' => 'required|exists:branches,id',
            'service' => 'nullable|string|max:60',
            'carriage_mode' => 'required|in:passenger,air_cargo,sea,courier',
            'collection_type' => 'required|in:drop_off,pickup',
            'supplier_name' => 'nullable|string|max:120',
            'supplier_order_ref' => 'nullable|string|max:80',
            'goods_description' => 'nullable|string|max:2000',
            'expected_packages' => 'nullable|integer|min:1|max:999',
            'declared_weight' => 'nullable|numeric|min:0',
        ]);
        $b = $this->bookings->create($data, $r->user()->id);
        return back()->with('success', "Booking {$b->reference} created.");
    }

    public function bookingConfirm(CargoBooking $booking)
    {
        $this->bookings->confirm($booking);
        return back()->with('success', 'Booking confirmed.');
    }

    public function bookingMatch(Request $r, CargoBooking $booking)
    {
        $data = $r->validate(['package_id' => 'required|exists:cargo_packages,id']);
        $this->bookings->matchPackage($booking, CargoPackage::findOrFail($data['package_id']), $r->user()->id);
        return back()->with('success', 'Package matched — tracking number assigned.');
    }

    /** Management profitability — group and per-branch margins (spec §15.8). */
    public function profitability(Request $r)
    {
        $profit = app(\Modules\Cargo\Services\ShipmentProfitService::class);
        $branches = \Modules\Organization\Models\Branch::active()->orderBy('name')->get();
        return view('cargo::consolidation.profitability', [
            'group' => $profit->summary(),
            'branches' => $branches->map(fn ($b) => ['branch' => $b, 'summary' => $profit->summary($b->id)]),
            'trips' => CargoTrip::with('boxes')->latest()->limit(25)->get()
                ->map(fn ($t) => ['trip' => $t, 'profit' => $profit->tripProfit($t)]),
        ]);
    }

    // ---- Billing (spec §13-14) ----

    /** Price preview — freight + storage + delivery for a package. */
    public function packageQuote(CargoPackage $package, Request $r)
    {
        return response()->json(
            app(\Modules\Cargo\Services\PackageBillingService::class)
                ->quote($package, $r->only(['service_type', 'zone_id', 'handling_fee', 'pickup_fee', 'currency'])));
    }

    /** Create the draft customer invoice for a package. */
    public function packageInvoice(Request $r, CargoPackage $package)
    {
        $data = $r->validate([
            'service_type' => 'nullable|string|max:60',
            'zone_id' => 'nullable|exists:delivery_zones,id',
            'handling_fee' => 'nullable|numeric|min:0',
            'pickup_fee' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|size:3',
        ]);
        $invoice = app(\Modules\Cargo\Services\PackageBillingService::class)
            ->invoice($package, $data, $r->user()->id);
        return back()->with('success', "Invoice {$invoice->reference} drafted — total {$invoice->total} {$invoice->currency}.");
    }

    /** Unmatched queue — packages with no customer/booking. */
    public function unmatched()
    {
        return view('cargo::consolidation.unmatched', [
            'packages' => CargoPackage::where('unidentified', true)->latest()->paginate(25),
        ]);
    }
}
