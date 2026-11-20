<?php
namespace Modules\Shipment\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Shipment\Models\{Airline, Flight};

class AirlineController extends Controller
{
    public function index() { return view('shipment::airlines', ['airlines' => Airline::latest()->paginate(20)]); }
    public function store(Request $r)
    {
        abort_unless($r->user()->can('shipment.airline.manage'), 403);
        $d = $r->validate(['name' => 'required|string', 'code' => 'required|string|max:10|unique:airlines,code',
            'iata_code' => 'nullable|string|max:3', 'icao_code' => 'nullable|string|max:4',
            'contact_email' => 'nullable|email', 'contact_phone' => 'nullable|string|max:40',
            'cargo_restrictions' => 'nullable|string', 'contract_ref' => 'nullable|string']);
        Airline::create($d + ['status' => 'active']);
        return back()->with('success', 'Airline added.');
    }
    public function flights() { return view('shipment::flights', ['flights' => Flight::with('airline')->latest('scheduled_departure')->paginate(20)]); }
    public function storeFlight(Request $r)
    {
        abort_unless($r->user()->can('shipment.flight.manage'), 403);
        $d = $r->validate(['airline_id' => 'required|exists:airlines,id', 'flight_no' => 'required|string',
            'departure_airport' => 'nullable|string|max:10', 'arrival_airport' => 'nullable|string|max:10',
            'scheduled_departure' => 'nullable|date', 'scheduled_arrival' => 'nullable|date']);
        Flight::create($d);
        return back()->with('success', 'Flight scheduled.');
    }
    public function flightEvent(Request $r, Flight $flight)
    {
        abort_unless($r->user()->can('shipment.flight.manage'), 403);
        $d = $r->validate(['event' => 'required|in:departed,in_transit,landed,delayed,cancelled,diverted,offloaded,rescheduled,boarding',
            'source' => 'required|in:airline_api,manual,airport,tracking_provider']);
        app(\Modules\Shipment\Services\ShipmentService::class)->recordFlightEvent($flight, $d['event'], $d['source'], $r->user()->id);
        return back()->with('success', 'Flight event recorded.');
    }
}
