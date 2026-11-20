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

        $trips->arrive($trip->fresh());
        $trips->handoverTanzania($trip->fresh(), $this->ops->id);
        $this->assertSame('reconciled', $trip->fresh()->status);
        $this->assertSame('opened', $box->fresh()->status);
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
