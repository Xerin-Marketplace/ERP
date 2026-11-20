<?php

namespace Modules\Cargo\Tests\Feature;

use App\Enums\AccountStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Arrival\Models\ReleaseApproval;
use Modules\Arrival\Models\StorageRateSetting;
use Modules\Arrival\Services\ReleaseService;
use Modules\Arrival\Services\StorageService;
use Modules\Cargo\Database\Seeders\CargoDatabaseSeeder;
use Modules\Cargo\Models\CargoBooking;
use Modules\Cargo\Models\CargoBox;
use Modules\Cargo\Models\CargoPackage;
use Modules\Cargo\Models\CargoTrip;
use Modules\Cargo\Services\BookingService;
use Modules\Cargo\Services\ConsolidationService;
use Modules\Cargo\Services\TripService;
use Modules\Commercial\Models\Customer;
use Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder;
use Modules\Organization\Models\Branch;
use Modules\Organization\Models\Company;
use Tests\TestCase;

class ConsolidationTest extends TestCase
{
    use RefreshDatabase;

    private Branch $dxb;
    private Branch $tz;
    private User $ops;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(OrganizationDatabaseSeeder::class);
        $this->seed(CargoDatabaseSeeder::class);

        $company = Company::where('code', 'XG')->firstOrFail();
        $this->dxb = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'DXB'],
            ['name' => 'Dubai', 'status' => 'active']);
        $this->tz = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'TZ'],
            ['name' => 'Tanzania', 'status' => 'active']);
        $this->ops = User::factory()->create(['status' => AccountStatus::Active]);
    }

    private function package(array $over = []): CargoPackage
    {
        return CargoPackage::forceCreate(array_merge([
            'reference' => 'PK-'.uniqid(), 'branch_id' => $this->dxb->id,
            'gross_weight' => 10, 'status' => 'received', 'unidentified' => false,
        ], $over));
    }

    private function customer(): Customer
    {
        static $n = 0; $n++;
        return Customer::forceCreate([
            'code' => 'C'.$n, 'company_id' => Company::where('code','XG')->first()->id,
            'name' => 'Cust '.$n, 'is_active' => true, 'branch_id' => $this->dxb->id,
        ]);
    }

    public function test_booking_ref_and_tracking_generated(): void
    {
        $b = app(BookingService::class)->create([
            'customer_id' => $this->customer()->id, 'branch_id' => $this->dxb->id,
            'carriage_mode' => 'passenger', 'collection_type' => 'drop_off',
        ], $this->ops->id);
        $this->assertMatchesRegularExpression('/^BK-\d{4}-\d{6}$/', $b->reference);

        $p = $this->package(['unidentified' => true]);
        app(BookingService::class)->matchPackage($b, $p, $this->ops->id);
        $p = $p->fresh();
        $this->assertFalse($p->unidentified);
        $this->assertMatchesRegularExpression('/^XRN\d{9}$/', $p->tracking_no);
        $this->assertSame($b->id, $p->booking_id);
    }

    public function test_box_close_requires_weight_reconciliation_and_seal(): void
    {
        $svc = app(ConsolidationService::class);
        $box = $svc->createBox($this->dxb->id, [], $this->ops->id);
        $svc->addPackage($box, $this->package(['gross_weight' => 10]), 10, $this->ops->id);
        $svc->addPackage($box, $this->package(['gross_weight' => 12]), 12, $this->ops->id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $svc->close($box, 30, 'SEAL-1', $this->ops->id); // 22 vs 30 — way over tolerance
    }

    public function test_full_box_trip_lifecycle(): void
    {
        $svc = app(ConsolidationService::class);
        $trips = app(TripService::class);

        $box = $svc->createBox($this->dxb->id, [], $this->ops->id);
        $svc->addPackage($box, $this->package(), 10, $this->ops->id);
        $box = $svc->close($box->fresh(), 10.2, 'SEAL-9', $this->ops->id);
        $this->assertSame('closed', $box->status);

        $trip = $trips->create([
            'passenger_name' => 'Fahad', 'origin_branch_id' => $this->dxb->id,
            'destination_branch_id' => $this->tz->id, 'airline' => 'Emirates',
            'flight_number' => 'EK725', 'flight_date' => now()->addDay()->toDateString(),
            'baggage_allowance' => 100, 'passenger_fee' => 50, 'currency' => 'AED',
        ], $this->ops->id);
        $this->assertMatchesRegularExpression('/^TR-\d{4}-\d{4}$/', $trip->reference);

        $svc->assignToTrip($box, $trip, $this->ops->id);
        $this->assertSame('assigned', $trip->fresh()->status);

        $trips->handoverDubai($trip->fresh(), $this->ops->id);
        $this->assertSame('handed_over', $trip->fresh()->status);

        // Departure requires evidence.
        try { $trips->depart($trip->fresh(), null, $this->ops->id); $this->fail('expected 422'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {}
        $trips->depart($trip->fresh(), 99, $this->ops->id);
        $this->assertSame('departed', $trip->fresh()->status);
        $this->assertSame('departed', $box->fresh()->status);

        // §8.3 — passenger fee auto-recorded as DXB→TZ inter-branch charge.
        $ib = \Modules\Finance\Models\InterBranchEntry::where('source_type', 'cargo_trip')
            ->where('source_id', $trip->id)->first();
        $this->assertNotNull($ib);
        $this->assertSame(50.0, (float) $ib->amount);
        $this->assertSame($this->dxb->id, $ib->spent_branch_id);
        $this->assertSame($this->tz->id, $ib->owed_branch_id);

        $trips->arrive($trip->fresh());
        $trips->handoverTanzania($trip->fresh(), $this->ops->id);
        $this->assertSame('reconciled', $trip->fresh()->status);
        $this->assertSame('opened', $box->fresh()->status);
    }

    public function test_box_loads_onto_shipment_awb_leg(): void
    {
        $svc = app(ConsolidationService::class);

        $box = $svc->createBox($this->dxb->id, [], $this->ops->id);
        $p1 = $this->package(); $p2 = $this->package();
        $svc->addPackage($box, $p1, 10, $this->ops->id);
        $svc->addPackage($box, $p2, 12, $this->ops->id);
        $box = $svc->close($box->fresh(), 22.1, 'SEAL-AWB', $this->ops->id);

        $shipment = new \Modules\Shipment\Models\Shipment([
            'origin_branch_id' => $this->dxb->id, 'destination_branch_id' => $this->tz->id,
            'status' => 'booked', 'origin_airport' => 'DXB', 'destination_airport' => 'DAR',
        ]);
        $shipment->reference = 'XER-SHP-2026-TEST1';
        $shipment->save();
        $box = $svc->assignToShipment($box, $shipment, $this->ops->id);

        $this->assertSame('assigned', $box->status);
        $this->assertSame($shipment->id, $box->shipment_id);
        $this->assertNull($box->trip_id);
        $this->assertSame(2, \Modules\Shipment\Models\ShipmentPackage::where('shipment_id', $shipment->id)->count());

        // Idempotent — re-assign adds no duplicate rows.
        $box->update(['status' => 'closed']);
        $svc->assignToShipment($box->fresh(), $shipment, $this->ops->id);
        $this->assertSame(2, \Modules\Shipment\Models\ShipmentPackage::where('shipment_id', $shipment->id)->count());

        // Past loading window → rejected.
        $departed = new \Modules\Shipment\Models\Shipment([
            'origin_branch_id' => $this->dxb->id, 'status' => 'departed',
        ]);
        $departed->reference = 'XER-SHP-2026-TEST2';
        $departed->save();
        $box->update(['status' => 'closed']);
        try { $svc->assignToShipment($box->fresh(), $departed, $this->ops->id); $this->fail('expected 422'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {}
    }

    public function test_status_notifications_fan_out_to_customer_channels(): void
    {
        $package = $this->package(['customer_id' => $this->customer()->id, 'tracking_no' => 'XRN900001']);
        $svc = app(\Modules\Cargo\Services\CargoService::class);

        // Customer-safe milestone → in_app + email + whatsapp + sms rows.
        $svc->log($package, 'received', $this->ops->id);
        $msgs = \Modules\Portal\Models\CustomerMessage::where('customer_id', $package->customer_id)->get();
        $this->assertCount(4, $msgs);
        $this->assertStringContainsString($package->tracking_no, $msgs->first()->body);
        $this->assertSame('queued', $msgs->firstWhere('channel', 'whatsapp')->status);
        $this->assertSame('sent', $msgs->firstWhere('channel', 'in_app')->status);

        // Internal-only event → nothing.
        $svc->log($package, 'weighed', $this->ops->id);
        $this->assertSame(4, \Modules\Portal\Models\CustomerMessage::count());
    }

    public function test_status_notifications_respect_channel_preferences(): void
    {
        $package = $this->package(['customer_id' => $this->customer()->id, 'tracking_no' => 'XRN900002']);
        \Modules\Portal\Models\CommunicationPreference::create([
            'customer_id' => $package->customer_id,
            'channels' => ['whatsapp' => false, 'sms' => false, 'email' => true, 'in_app' => true],
        ]);
        app(\Modules\Cargo\Services\CargoService::class)->log($package, 'arrived', $this->ops->id);
        $channels = \Modules\Portal\Models\CustomerMessage::pluck('channel')->all();
        sort($channels);
        $this->assertSame(['email', 'in_app'], $channels);
    }

    public function test_trip_departure_notifies_package_customers(): void
    {
        $svc = app(ConsolidationService::class);
        $trips = app(TripService::class);
        $package = $this->package(['customer_id' => $this->customer()->id, 'tracking_no' => 'XRN900003']);

        $box = $svc->createBox($this->dxb->id, [], $this->ops->id);
        $svc->addPackage($box, $package, 10, $this->ops->id);
        $box = $svc->close($box->fresh(), 10.1, 'SEAL-N', $this->ops->id);

        $trip = $trips->create(['passenger_name' => 'X', 'origin_branch_id' => $this->dxb->id,
            'destination_branch_id' => $this->tz->id, 'flight_date' => now()->addDay()->toDateString()], $this->ops->id);
        $svc->assignToTrip($box, $trip, $this->ops->id);
        $trips->handoverDubai($trip->fresh(), $this->ops->id);
        $trips->depart($trip->fresh(), 1, $this->ops->id);

        $this->assertNotNull($package->fresh()->events()->where('event', 'departed')->first());
        $this->assertStringContainsString('departed for Tanzania',
            \Modules\Portal\Models\CustomerMessage::where('customer_id', $package->customer_id)->latest()->first()->body);
    }

    public function test_unmatched_package_cannot_be_consolidated(): void
    {
        $svc = app(ConsolidationService::class);
        $box = $svc->createBox($this->dxb->id, [], $this->ops->id);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $svc->addPackage($box, $this->package(['unidentified' => true]), 5, $this->ops->id);
    }

    public function test_release_requires_paid_invoice_and_generates_otp(): void
    {
        $p = $this->package();
        $release = app(ReleaseService::class);

        $a = $release->request($p, null, $this->tz->id, $this->ops->id);
        $this->assertMatchesRegularExpression('/^RA-\d{4}-\d{5}$/', $a->reference);
        $this->assertSame('pending', $a->status);

        // Hold requires reason.
        try { $release->decide($a, $this->ops->id, 'on_hold'); $this->fail(); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {}

        $a = $release->decide($a->fresh(), $this->ops->id, 'approved');
        $this->assertSame('approved', $a->status);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $a->release_code);

        // Code gates hand-over, burns on use.
        $this->assertTrue($release->canRelease($p));
        $this->assertFalse($release->verifyCode($p, '000000'));
        $this->assertTrue($release->verifyCode($p, $a->release_code));
        $this->assertFalse($release->canRelease($p)); // used → released
    }

    public function test_release_blocked_on_unpaid_invoice(): void
    {
        $customer = $this->customer();
        $invoice = \Modules\Finance\Models\CustomerInvoice::forceCreate([
            'reference' => 'INV-TZ-1', 'company_id' => $customer->company_id,
            'customer_id' => $customer->id, 'invoice_date' => now()->toDateString(),
            'currency' => 'TZS', 'total' => 10000, 'received_amount' => 0,
            'status' => 'issued', 'created_by' => $this->ops->id,
        ]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        app(ReleaseService::class)->request($this->package(), $invoice->id, $this->tz->id, $this->ops->id);
    }

    public function test_package_quote_and_invoice_from_rate_card(): void
    {
        \Modules\Commercial\Models\RateCard::forceCreate([
            'code' => 'RC-AIR-1', 'name' => 'DXB→DAR air', 'service_type' => 'air_cargo',
            'origin_country' => 'United Arab Emirates', 'destination_country' => 'Tanzania',
            'currency' => 'TZS', 'base_rate' => 5000, 'per_kg_rate' => 2500,
            'minimum_charge' => 3000, 'status' => 'active', 'priority' => 1,
        ]);
        $customer = $this->customer();
        $p = $this->package(['customer_id' => $customer->id, 'gross_weight' => 10]);

        $billing = app(\Modules\Cargo\Services\PackageBillingService::class);
        $q = $billing->quote($p, []);
        $this->assertSame(10.0, $q['freight']['kg']);
        $this->assertSame(30000.0, $q['freight']['amount']); // 5000 + 10×2500
        $this->assertSame('RC-AIR-1', $q['freight']['rate_card']);

        $inv = $billing->invoice($p, [], $this->ops->id);
        $this->assertSame('draft', $inv->status);
        $this->assertSame(30000.0, (float) $inv->total);
        $this->assertSame(1, $inv->lines->count());
        $this->assertStringContainsString('Freight', $inv->lines->first()->description);
        // Revenue account 4110 created idempotently.
        $this->assertSame('4110', $inv->lines->first()->account->code ?? \Modules\Finance\Models\ChartOfAccount::find($inv->lines->first()->account_id)->code);
    }

    public function test_invoice_blocked_without_customer_or_charges(): void
    {
        $p = $this->package(); // no customer
        try {
            app(\Modules\Cargo\Services\PackageBillingService::class)->invoice($p, [], $this->ops->id);
            $this->fail('expected abort');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {}

        // Customer but no rates/fees → nothing to invoice.
        $p2 = $this->package(['customer_id' => $this->customer()->id]);
        try {
            app(\Modules\Cargo\Services\PackageBillingService::class)->invoice($p2, [], $this->ops->id);
            $this->fail('expected abort');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {}
        $this->assertTrue(true);
    }

    public function test_shipment_profit_revenue_minus_leg_and_delivery_costs(): void
    {
        $svc = app(ConsolidationService::class);
        $billing = app(\Modules\Cargo\Services\PackageBillingService::class);
        $profit = app(\Modules\Cargo\Services\ShipmentProfitService::class);

        \Modules\Commercial\Models\RateCard::forceCreate([
            'code' => 'RC-1', 'name' => 'Air', 'service_type' => 'air_cargo',
            'currency' => 'TZS', 'base_rate' => 0, 'per_kg_rate' => 2000,
            'minimum_charge' => 0, 'status' => 'active', 'priority' => 1,
        ]);

        $customer = $this->customer();
        $p1 = $this->package(['customer_id' => $customer->id, 'gross_weight' => 10]);
        $p2 = $this->package(['customer_id' => $customer->id, 'gross_weight' => 30]);
        $box = $svc->createBox($this->dxb->id, [], $this->ops->id);
        $svc->addPackage($box, $p1, 10, $this->ops->id);
        $svc->addPackage($box, $p2, 30, $this->ops->id);
        $box = $svc->close($box->fresh(), 40, 'SEAL-P', $this->ops->id);

        $trip = app(TripService::class)->create([
            'passenger_name' => 'F', 'origin_branch_id' => $this->dxb->id,
            'destination_branch_id' => $this->tz->id, 'passenger_fee' => 40000, 'currency' => 'TZS',
        ], $this->ops->id);
        $svc->assignToTrip($box, $trip, $this->ops->id);

        // Invoice p1 only → revenue 10kg×2000 = 20,000 posted.
        $inv = $billing->invoice($p1, ['currency' => 'TZS'], $this->ops->id);
        $inv->update(['status' => 'posted', 'received_amount' => $inv->total]);

        $pp = $profit->packageProfit($p1);
        $this->assertSame(20000.0, $pp['revenue']);
        $this->assertSame(10000.0, $pp['trip_cost']); // 40000 × 10/40
        $this->assertSame(10000.0, $pp['profit']);

        $tp = $profit->tripProfit($trip->fresh());
        $this->assertSame(20000.0, $tp['revenue']);
        $this->assertSame(40000.0, $tp['leg_cost']);
        $this->assertSame(-20000.0, $tp['profit']); // loss-making leg flagged
    }

    public function test_release_blocked_when_customer_over_credit_limit(): void
    {
        $customer = $this->customer();
        $customer->update(['credit_limit' => 5000]);
        \Modules\Finance\Models\CustomerInvoice::forceCreate([
            'reference' => 'INV-X', 'company_id' => $customer->company_id,
            'customer_id' => $customer->id, 'package_id' => null,
            'invoice_date' => now()->toDateString(), 'currency' => 'TZS',
            'total' => 9000, 'received_amount' => 0, 'status' => 'posted',
            'created_by' => $this->ops->id,
        ]);
        $p = $this->package(['customer_id' => $customer->id]);
        try {
            app(ReleaseService::class)->request($p, null, $this->tz->id, $this->ops->id);
            $this->fail('expected credit-limit block');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('credit limit', $e->errors()['credit'][0]);
        }
        // No limit configured → allowed.
        $customer->update(['credit_limit' => null]);
        $a = app(ReleaseService::class)->request($this->package(['customer_id' => $customer->id]), null, $this->tz->id, $this->ops->id);
        $this->assertSame('pending', $a->status);
    }

    public function test_guest_tracking_requires_xrn_and_contact_match(): void
    {
        $customer = $this->customer();
        $customer->update(['email' => 'cust@x.com', 'phone' => '+255700111222']);
        $p = $this->package(['customer_id' => $customer->id, 'tracking_no' => 'XRN123456789']);

        $svc = app(\Modules\Portal\Services\PortalService::class);
        $this->assertNull($svc->guestTrack('XRN123456789', 'wrong@x.com'));
        $this->assertNull($svc->guestTrack('XRN000000000', 'cust@x.com'));
        $r = $svc->guestTrack('xrn123456789', 'cust@x.com');
        $this->assertSame('XRN123456789', $r['tracking_no']);

        // Web route works for guests.
        $this->get(route('track.form'))->assertOk();
        $this->post(route('track.lookup'), [
            'tracking_no' => 'XRN123456789', 'contact' => 'cust@x.com',
        ])->assertOk()->assertSee('XRN123456789');
    }

    public function test_profit_drilldown_sorts_worst_first_and_flags_losses(): void
    {
        $svc = app(ConsolidationService::class);
        $trips = app(TripService::class);

        // Loss-making package: trip fee 100, no revenue.
        $loser = $this->package();
        $box = $svc->createBox($this->dxb->id, [], $this->ops->id);
        $svc->addPackage($box, $loser, 10, $this->ops->id);
        $box = $svc->close($box->fresh(), 10.0, 'SEAL-D', $this->ops->id);
        $trip = $trips->create(['passenger_name' => 'L', 'origin_branch_id' => $this->dxb->id,
            'destination_branch_id' => $this->tz->id, 'flight_date' => now()->addDay()->toDateString(),
            'passenger_fee' => 100], $this->ops->id);
        $svc->assignToTrip($box, $trip, $this->ops->id);

        $neutral = $this->package(); // no costs, no revenue

        $rows = app(\Modules\Cargo\Services\ShipmentProfitService::class)->drilldown();
        $this->assertSame($loser->id, $rows->first()['package']->id);
        $this->assertTrue($rows->first()['loss']);
        $this->assertLessThan(0, $rows->first()['profit']);
        $this->assertFalse($rows->last()['loss']);
    }

    public function test_storage_charge_free_days_then_rate(): void
    {
        StorageRateSetting::create([
            'branch_id' => null, 'free_days' => 3, 'rate_per_kg_day' => 500,
            'currency' => 'TZS', 'effective_from' => now()->subYear()->toDateString(),
        ]);
        $p = $this->package(['gross_weight' => 10, 'branch_id' => $this->tz->id]);
        $storage = app(StorageService::class);

        $free = $storage->charge($p, now()->subDays(2));
        $this->assertSame(0.0, $free['charge']);

        $charged = $storage->charge($p, now()->subDays(8));
        $this->assertSame(8, $charged['days']);
        $this->assertSame(5, $charged['chargeable_days']);
        $this->assertSame(25000.0, $charged['charge']); // 5 days × 10kg × 500
    }
}

// Guest tracking coverage (spec §12.2) — lives here since it uses cargo fixtures.
