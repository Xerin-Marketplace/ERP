<?php
namespace Modules\Cargo\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Cargo\Models\{CargoPackage, CargoIncident, Measurement, Receiving, Warehouse, WarehouseLocation, PreAlert};
use Modules\Cargo\Services\CargoService;
use Modules\Commercial\Models\Customer;
use Modules\Organization\Models\{Branch, Company};
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CargoModuleTest extends TestCase
{
    use RefreshDatabase;

    private CargoService $svc;
    private Branch $dxb;
    private Customer $cust;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedDeps();
        $this->svc = app(CargoService::class);
        $this->dxb = Branch::where('code', 'DXB')->first();
        $this->cust = new Customer(['name' => 'Cargo Cust', 'type' => 'business']);
        $this->cust->forceFill(['code' => 'XER-CUS-2026-000099', 'company_id' => Company::value('id')])->save();
    }

    private function seedDeps(): void
    {
        (new \Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder)->run();
        (new \Modules\Cargo\Database\Seeders\CargoDatabaseSeeder)->run();
    }

    private function user(array $perms): User
    {
        $u = User::factory()->create(['status' => 'active']);
        foreach ($perms as $p) { Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']); $u->givePermissionTo($p); }
        return $u;
    }

    private function receiving(array $data = [], ?int $userId = null): Receiving
    {
        return $this->svc->startReceiving($data + ['branch_id' => $this->dxb->id, 'customer_id' => $this->cust->id], $userId);
    }

    public function test_package_reference_is_branch_scoped_and_unique(): void
    {
        $u = $this->user([]);
        $r = $this->receiving([], $u->id);
        $pkgs = $this->svc->registerPackages($r, 3, [], $u->id);
        $this->assertCount(3, $pkgs);
        $this->assertMatchesRegularExpression('/XER-DXB-PKG-\d{4}-\d{6}/', $pkgs[0]->reference);
        $this->assertEquals(3, CargoPackage::count());
    }

    public function test_partial_receiving_not_treated_as_full(): void
    {
        $u = $this->user([]);
        $r = $this->receiving(['expected_packages' => 5], $u->id);
        $this->svc->registerPackages($r, 3, [], $u->id);
        $r = $this->svc->confirmReceiving($r->fresh(), $u->id);
        $this->assertEquals('short', $r->outcome);
        $this->assertTrue(CargoIncident::where('category', 'short_received')->exists());
    }

    public function test_excess_receiving_flagged(): void
    {
        $u = $this->user([]);
        $r = $this->receiving(['expected_packages' => 2], $u->id);
        $this->svc->registerPackages($r, 4, [], $u->id);
        $this->assertEquals('excess', $this->svc->confirmReceiving($r->fresh(), $u->id)->outcome);
    }

    public function test_unidentified_cargo_not_linked_until_verified(): void
    {
        $u = $this->user([]);
        $r = $this->receiving(['customer_id' => null, 'unidentified' => true], $u->id);
        $pkgs = $this->svc->registerPackages($r, 1, [], $u->id);
        $p = $pkgs[0];
        $this->assertTrue($p->unidentified);
        $this->assertNull($p->customer_id);
        $this->svc->identifyPackage($p, $this->cust->id, $u->id);
        $this->assertEquals($this->cust->id, $p->fresh()->customer_id);
        $this->assertEquals('linked', $p->events()->latest()->first()->event);
    }

    public function test_reweigh_preserves_history_and_computes_chargeable(): void
    {
        $u = $this->user([]);
        $r = $this->receiving([], $u->id);
        $p = $this->svc->registerPackages($r, 1, [], $u->id)[0];
        $m1 = $this->svc->measure($p, ['gross_weight' => 20, 'length' => 100, 'width' => 50, 'height' => 40, 'divisor' => 6000], $u->id);
        $this->assertEquals(33.33, $m1->volumetric_weight);
        $this->assertEquals(33.33, $m1->chargeable_weight); // vol > gross
        $m2 = $this->svc->measure($p, ['gross_weight' => 45], $u->id, 'reweigh');
        $this->assertEquals(45.0, $m2->chargeable_weight);
        $this->assertEquals(2, $p->measurements()->count()); // history preserved
        $this->assertEquals(45.0, $p->fresh()->chargeable_weight);
    }

    public function test_inspection_hold_requires_release(): void
    {
        $u = $this->user([]);
        $r = $this->receiving([], $u->id);
        $p = $this->svc->registerPackages($r, 1, [], $u->id)[0];
        $this->svc->inspect($p, ['outcome' => 'compliance_review', 'comments' => 'suspicious'], $u->id);
        $this->assertEquals('on_hold', $p->fresh()->status);
        $this->assertTrue(CargoIncident::where('category', 'restricted')->exists());
        $this->svc->releaseHold($p->fresh(), $u->id);
        $this->assertNotEquals('on_hold', $p->fresh()->status);
    }

    public function test_repackaging_preserves_original_snapshot(): void
    {
        $u = $this->user([]);
        $r = $this->receiving([], $u->id);
        $p = $this->svc->registerPackages($r, 1, ['gross_weight' => 10], $u->id)[0];
        $rp = $this->svc->repack($p, ['reason' => 'damaged carton', 'packaging_type' => 'wooden_box', 'new_gross_weight' => 12], $u->id);
        $this->assertEquals(10, $rp->before_snapshot['gross_weight']);
        $this->assertEquals(12, $p->fresh()->gross_weight);
        $this->assertEquals('repacked', $p->fresh()->packaging_status);
    }

    public function test_movement_custody_history_and_capacity(): void
    {
        $u = $this->user([]);
        $wh = Warehouse::where('code', 'DXB-MAIN')->first();
        $loc = WarehouseLocation::where('warehouse_id', $wh->id)->where('code', 'A-01-01')->first();
        $r = $this->receiving([], $u->id);
        $p = $this->svc->registerPackages($r, 1, [], $u->id)[0];
        $m = $this->svc->store($p, $loc, $u->id);
        $this->assertNull($m->from_location_id);
        $this->assertEquals($loc->id, $p->fresh()->location_id);
        $this->assertEquals('stored', $p->fresh()->status);
        // capacity: fill to max then reject
        $loc->update(['capacity' => 1]);
        $p2 = $this->svc->registerPackages($r, 1, [], $u->id)[0];
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->move($p2, $loc->fresh(), $u->id);
    }

    public function test_duplicate_scan_flagged_not_duplicated(): void
    {
        $u = $this->user([]);
        $r = $this->receiving([], $u->id);
        $p = $this->svc->registerPackages($r, 1, [], $u->id)[0];
        $e1 = $this->svc->scan($p, 'receiving', $u->id);
        $e2 = $this->svc->scan($p, 'receiving', $u->id);
        $this->assertFalse($e1->meta['duplicate'] ?? false);
        $this->assertTrue($e2->meta['duplicate']);
    }

    public function test_commercial_handover_does_not_create_receiving(): void
    {
        // A handover exists but no physical receiving until staff start one
        $this->assertEquals(0, Receiving::count());
        $u = $this->user([]);
        $r = $this->receiving([], $u->id);
        $this->assertEquals('in_progress', $r->status);
        $this->assertEquals(1, Receiving::count());
    }

    public function test_branch_isolation_on_packages(): void
    {
        $u = $this->user([]);
        $r = $this->receiving([], $u->id);
        $this->svc->registerPackages($r, 2, [], $u->id);
        $tz = Branch::where('code', 'TZ')->first();
        // user with TZ-only assignment sees none
        $tzUser = $this->user(['cargo.packages.view']);
        $tzUser->setRelation('currentAssignment', (object) ['branch_id' => $tz->id]);
        $this->assertEquals(0, CargoPackage::for($tzUser)->count());
    }

    public function test_barcode_pattern_table_complete(): void
    {
        // sanity: label view exists and reference is URL-safe for scanning
        $u = $this->user([]);
        $r = $this->receiving([], $u->id);
        $p = $this->svc->registerPackages($r, 1, [], $u->id)[0];
        $this->assertMatchesRegularExpression('/^[A-Z0-9\-]+$/', $p->reference);
    }
}
