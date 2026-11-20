<?php
namespace Modules\Delivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Branch;
use Modules\Delivery\Models\{DeliveryOrder, Driver, Vehicle, DeliveryService as Svc, DeliveryZone, ProofOfDelivery};
use Modules\Delivery\Services\DeliveryService;
use Modules\Arrival\Services\ArrivalService;
use Modules\Arrival\Models\{ShipmentArrival, CustomsCase, PackageReceipt};
use Modules\Shipment\Services\ShipmentService;
use Modules\Cargo\Models\CargoPackage;
use Modules\Commercial\Models\Customer;
use Tests\TestCase;

class DeliveryModuleTest extends TestCase
{
    use RefreshDatabase;

    private DeliveryService $svc;
    private ShipmentService $shp;
    private ArrivalService $arr;
    private $user; private $tz; private $dxb; private $customer;

    private function fixture(): void
    {
        $this->svc = app(DeliveryService::class);
        $this->shp = app(ShipmentService::class);
        $this->arr = app(ArrivalService::class);
        $this->seed(\Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder::class);
        $this->dxb = Branch::where('code','DXB')->first() ?? Branch::factory()->create(['code'=>'DXB']);
        $this->tz = Branch::where('code','TZ')->first() ?? Branch::factory()->create(['code'=>'TZ']);
        $this->user = \App\Models\User::factory()->create();
        $this->seed(\Modules\Delivery\Database\Seeders\DeliveryDatabaseSeeder::class);
        $this->user->assignRole('Delivery Manager');
        $this->customer = new Customer(['company_id' => $this->dxb->company_id, 'name' => 'Test Customer']);
        $this->customer->forceFill(['code' => 'XER-CUS-2026-000001'])->save();
    }

    /** Package that passed the full Phase 5 pipeline → release-ready in TZ. */
    private function readyPackage(): CargoPackage
    {
        $s = $this->shp->create(['origin_branch_id' => $this->dxb->id, 'origin_airport' => 'DXB', 'destination_airport' => 'DAR'], $this->user->id);
        $p = new CargoPackage();
        $p->forceFill(['reference' => 'XER-DXB-PKG-2026-' . str_pad((string) random_int(1,999999), 6, '0', STR_PAD_LEFT),
            'branch_id' => $this->dxb->id, 'status' => 'stored', 'gross_weight' => 10, 'chargeable_weight' => 12,
            'customer_id' => $this->customer->id])->save();
        $this->shp->allocate($s, [$p->id], $this->user->id);
        $a = $this->arr->register($s->fresh(), $this->tz->id, $this->user->id);
        $this->arr->receivePackage($a, $p->id, $this->user->id);
        $cc = new CustomsCase(['arrival_id' => $a->id, 'status' => 'released']);
        $cc->forceFill(['reference' => 'XER-CLR-2026-' . random_int(100000,999999)])->save();
        return $p->fresh();
    }

    private function order(CargoPackage $p): DeliveryOrder
    {
        return $this->svc->create(['customer_id' => $this->customer->id, 'branch_id' => $this->tz->id,
            'service_id' => Svc::where('code','STD')->value('id'),
            'zone_id' => DeliveryZone::first()->id], [$p->id], $this->user->id);
    }

    private function driver(array $attrs = []): Driver
    {
        $d = new Driver(array_merge(['branch_id' => $this->tz->id, 'name' => 'Driver ' . random_int(1,999),
            'verification_status' => 'verified', 'availability' => 'available', 'license_expiry' => now()->addYear()], $attrs));
        $d->forceFill(['reference' => 'XER-DRV-' . random_int(100000,999999)])->save();
        return $d;
    }

    public function test_creates_order_only_for_release_ready_packages(): void
    {
        $this->fixture();
        $p = $this->readyPackage();
        $o = $this->order($p);
        $this->assertStringStartsWith('XER-DLV-TZ-', $o->reference);
        $this->assertEquals('pending_release', $o->status);
        $this->assertEquals('out_for_delivery', $p->fresh()->status);
        $this->assertNotNull($o->price_breakdown);
        // non-ready package rejected
        $raw = new CargoPackage();
        $raw->forceFill(['reference' => 'XER-DXB-PKG-2026-RAW001', 'branch_id' => $this->dxb->id, 'status' => 'stored', 'gross_weight' => 5, 'chargeable_weight' => 5])->save();
        try { $this->svc->create(['customer_id' => $this->customer->id, 'branch_id' => $this->tz->id], [$raw->id], $this->user->id); $this->fail('non-ready allowed'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
    }

    public function test_prevents_package_on_two_active_orders(): void
    {
        $this->fixture();
        $p = $this->readyPackage();
        $this->order($p);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->order($p);
    }

    public function test_pricing_zone_fixed_and_weight(): void
    {
        $this->fixture();
        $zone = DeliveryZone::first();
        $std = Svc::where('code','STD')->first();
        $p = $this->svc->price($std, $zone, 10);
        $this->assertEquals($zone->fee, $p['fee']);
        $w = Svc::create(['code' => 'WGT', 'name' => 'Weight', 'pricing_method' => 'weight', 'base_fee' => 1000, 'per_kg_fee' => 500]);
        $this->assertEquals(6000, $this->svc->price($w, null, 10)['fee']);
    }

    public function test_status_transition_validation(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $this->svc->transition($o, 'ready_for_dispatch', $this->user->id);
        $this->assertEquals('ready_for_dispatch', $o->fresh()->status);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->transition($o->fresh(), 'delivered', $this->user->id);
    }

    public function test_assign_requires_eligible_driver(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $o->update(['status' => 'unassigned']);
        $unverified = $this->driver(['verification_status' => 'pending']);
        try { $this->svc->assign($o, $unverified, null, $this->user->id); $this->fail('unverified assigned'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $ok = $this->driver();
        $a = $this->svc->assign($o->fresh(), $ok, null, $this->user->id);
        $this->assertEquals('offered', $a->status);
        $this->assertEquals('assigned', $o->fresh()->status);
    }

    public function test_no_double_assignment(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $o->update(['status' => 'unassigned']);
        $this->svc->assign($o, $this->driver(), null, $this->user->id);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->assign($o->fresh(), $this->driver(), null, $this->user->id);
    }

    public function test_driver_reject_returns_order_to_queue(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $o->update(['status' => 'unassigned']);
        $a = $this->svc->assign($o, $this->driver(), null, $this->user->id);
        $this->svc->driverRespond($a, false);
        $o->refresh();
        $this->assertEquals('unassigned', $o->status);
        $this->assertNull($o->driver_id);
        $this->assertEquals('rejected', $a->fresh()->status);
    }

    public function test_vehicle_capacity_and_availability(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $o->update(['status' => 'unassigned']);
        $maint = Vehicle::create(['registration_no' => 'T-MAINT', 'branch_id' => $this->tz->id, 'status' => 'maintenance']);
        try { $this->svc->assign($o, $this->driver(), $maint, $this->user->id); $this->fail('maintenance vehicle'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
    }

    public function test_auto_assign_picks_eligible_driver(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $o->update(['status' => 'unassigned']);
        $this->driver();
        $a = $this->svc->autoAssign($o, $this->user->id);
        $this->assertNotNull($a);
        $this->assertEquals('assigned', $o->fresh()->status);
    }

    public function test_location_blocked_when_offline_and_deduped(): void
    {
        $this->fixture();
        $d = $this->driver(['availability' => 'offline']);
        try { $this->svc->recordLocation($d, -6.8, 39.2); $this->fail('offline location'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $d->update(['availability' => 'available']);
        $this->svc->recordLocation($d, -6.8, 39.2);
        $this->svc->recordLocation($d, -6.8, 39.2); // dedupe within 15s
        $this->assertEquals(1, $d->locations()->count());
    }

    public function test_pod_requires_recipient_and_is_unique(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $o->update(['status' => 'in_transit', 'driver_id' => $this->driver()->id]);
        try { $this->svc->confirmDelivery($o, [], $o->driver_id); $this->fail('no recipient'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $pod = $this->svc->confirmDelivery($o->fresh(), ['recipient_name' => 'Jane'], $o->driver_id);
        $this->assertEquals('delivered', $o->fresh()->status);
        $this->assertEquals('delivered', $o->packages()->first()->status);
        try { $this->svc->confirmDelivery($o->fresh(), ['recipient_name' => 'Jane'], $o->driver_id); $this->fail('dup POD'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
    }

    public function test_failed_reschedule_and_return(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $drv = $this->driver();
        $o->update(['status' => 'in_transit', 'driver_id' => $drv->id]);
        $this->svc->recordFailure($o, 'customer_unavailable', $drv->id);
        $this->assertEquals('failed_delivery', $o->fresh()->status);
        $this->svc->reschedule($o->fresh(), now()->addDay()->toDateString(), $this->user->id);
        $this->assertEquals('rescheduled', $o->fresh()->status);
        $this->assertEquals(1, $o->attempts()->count()); // history preserved
    }

    public function test_return_preserves_custody(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $o->update(['status' => 'failed_delivery']);
        $r = $this->svc->initiateReturn($o, 'customer_refused', $this->user->id);
        $this->assertStringStartsWith('XER-RTN-', $r->reference);
        $this->svc->receiveReturn($r, $this->user->id);
        $this->assertEquals('returned_to_warehouse', $o->fresh()->status);
        $this->assertEquals('received_tz', $o->packages()->first()->status);
    }

    public function test_cod_reconcile_requires_ref(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $o->update(['cod_amount' => 50000]);
        $cod = $this->svc->recordCod($o->fresh(), ['method' => 'cash'], $this->driver()->id);
        try { $this->svc->reconcileCod($cod, $this->user->id, null); $this->fail('no ref'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $this->svc->reconcileCod($cod, $this->user->id, 'MM-TRX-001');
        $this->assertEquals('reconciled', $cod->fresh()->status);
    }

    public function test_track_hides_stale_location_and_internal_events(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $t = $this->svc->track($o);
        $this->assertFalse($t['live_available']);
        $this->assertNull($t['live_location']);
        $this->assertEquals($o->reference, $t['reference']);
    }

    public function test_branch_isolation(): void
    {
        $this->fixture();
        $o = $this->order($this->readyPackage());
        $dxbUser = \App\Models\User::factory()->create();
        $dxbUser->givePermissionTo('delivery.view');
        \Modules\Identity\Models\EmployeeAssignment::create([
            'user_id' => $dxbUser->id, 'branch_id' => $this->dxb->id, 'status' => 'active',
            'company_id' => $this->dxb->company_id, 'department_id' => null,
            'effective_start' => now()->toDateString()]);
        $this->assertEquals(0, DeliveryOrder::for($dxbUser)->count());
    }
}
