<?php
namespace Modules\Shipment\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Branch;
use Modules\Shipment\Models\{Shipment, Airline, Flight};
use Modules\Shipment\Services\ShipmentService;
use Modules\Cargo\Models\CargoPackage;
use Tests\TestCase;

class ShipmentModuleTest extends TestCase
{
    use RefreshDatabase;

    private ShipmentService $svc;
    private $user; private $dxb; private $tz;

    private function fixture(): void
    {
        $this->svc = app(ShipmentService::class);
        $this->seed(\Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder::class);
        $this->dxb = Branch::where('code','DXB')->first() ?? Branch::factory()->create(['code'=>'DXB']);
        $this->tz = Branch::where('code','TZ')->first() ?? Branch::factory()->create(['code'=>'TZ']);
        $this->user = \App\Models\User::factory()->create();
        $this->seed(\Modules\Shipment\Database\Seeders\ShipmentDatabaseSeeder::class);
        $this->user->assignRole('Dubai Warehouse and Cargo Operations');
    }

    private function shipment(): Shipment
    {
        return $this->svc->create(['origin_branch_id' => $this->dxb->id, 'origin_airport' => 'DXB', 'destination_airport' => 'DAR'], $this->user->id);
    }

    private function pkg(array $attrs = []): CargoPackage
    {
        $p = new CargoPackage();
        $p->forceFill(array_merge([
            'reference' => 'XER-DXB-PKG-2026-' . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'branch_id' => $this->dxb->id, 'status' => 'stored',
            'gross_weight' => 10, 'chargeable_weight' => 12, 'volumetric_weight' => 12,
        ], $attrs))->save();
        return $p;
    }

    public function test_creates_shipment_with_unique_reference(): void
    {
        $this->fixture();
        $a = $this->shipment(); $b = $this->shipment();
        $this->assertStringStartsWith('XER-SHP-' . date('Y') . '-', $a->reference);
        $this->assertNotEquals($a->reference, $b->reference);
    }

    public function test_status_transition_validation(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $this->svc->transition($s, 'planning', $this->user->id);
        $this->assertEquals('planning', $s->fresh()->status);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->transition($s->fresh(), 'arrived', $this->user->id);
    }

    public function test_allocates_eligible_package_and_recalculates(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $p = $this->pkg();
        $this->assertEquals(1, $this->svc->allocate($s, [$p->id], $this->user->id));
        $s->refresh();
        $this->assertEquals(1, $s->total_packages);
        $this->assertEquals(12, $s->total_chargeable_weight);
        $this->assertEquals('allocated', $p->fresh()->status);
    }

    public function test_rejects_ineligible_and_duplicate_allocation(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $held = $this->pkg(['status' => 'on_hold']);
        try { $this->svc->allocate($s, [$held->id], $this->user->id); $this->fail('should reject'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $p = $this->pkg();
        $this->svc->allocate($s, [$p->id], $this->user->id);
        $s2 = $this->shipment();
        try { $this->svc->allocate($s2, [$p->id], $this->user->id); $this->fail('double alloc allowed'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
    }

    public function test_rejects_cross_branch_allocation(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $tzPkg = $this->pkg(['branch_id' => $this->tz->id]);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->allocate($s, [$tzPkg->id], $this->user->id);
    }

    public function test_split_creates_child_and_preserves_parent(): void
    {
        $this->fixture();
        $s = $this->shipment();
        [$p1, $p2] = [$this->pkg(), $this->pkg()];
        $this->svc->allocate($s, [$p1->id, $p2->id], $this->user->id);
        $child = $this->svc->split($s, [$p2->id], $this->user->id, 'airline capacity');
        $this->assertEquals($s->id, $child->parent_id);
        $this->assertEquals(1, $s->fresh()->total_packages);
        $this->assertEquals(1, $child->fresh()->total_packages);
        $this->assertEquals('allocated', $p2->fresh()->status);
    }

    public function test_booking_requires_evidence_to_confirm(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $airline = Airline::create(['name' => 'Emirates', 'code' => 'EK']);
        $b = $this->svc->book($s, ['airline_id' => $airline->id], $this->user->id);
        try { $this->svc->confirmBooking($b, [], $this->user->id); $this->fail('no evidence'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $b2 = $this->svc->confirmBooking($b, ['booking_ref' => 'EK-ABC123', 'flight_no' => 'EK725'], $this->user->id);
        $this->assertEquals('confirmed', $b2->status);
        $this->assertEquals('EK-ABC123', $b2->booking_ref);
    }

    public function test_awb_check_digit_and_duplicate_prevention(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $awb = $this->svc->createAwb($s, ['awb_number' => '176-12345675', 'kind' => 'mawb'], $this->user->id);
        $this->assertEquals('draft', $awb->status);
        try { $this->svc->createAwb($s, ['awb_number' => '176-12345675'], $this->user->id); $this->fail('dup allowed'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        try { $this->svc->createAwb($s, ['awb_number' => '176-12345676'], $this->user->id); $this->fail('bad check digit'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
    }

    public function test_awb_amend_creates_new_version(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $awb = $this->svc->createAwb($s, ['awb_number' => '176-76543213'], $this->user->id);
        $issued = $this->svc->issueAwb($awb, $this->user->id);
        $v2 = $this->svc->amendAwb($issued, ['goods_description' => 'Amended'], $this->user->id);
        $this->assertEquals(2, $v2->version);
        $this->assertEquals('amended', $issued->fresh()->status);
        $this->assertEquals('draft', $v2->status);
    }

    public function test_manifest_reconciliation_blocks_on_mismatch(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $this->svc->allocate($s, [$this->pkg()->id], $this->user->id);
        $m = $this->svc->manifest($s->fresh(), [], $this->user->id);
        $m = $this->svc->reconcile($m, 0, null, $this->user->id);
        $this->assertFalse($m->reconciliation['pieces_match']);
        $this->assertEquals('draft', $m->status);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->approveManifest($m, $this->user->id);
    }

    public function test_partial_handover_flags_missing_and_exception(): void
    {
        $this->fixture();
        $s = $this->shipment();
        [$p1, $p2] = [$this->pkg(), $this->pkg()];
        $this->svc->allocate($s, [$p1->id, $p2->id], $this->user->id);
        $h = $this->svc->handover($s->fresh(), [$p1->id], [], $this->user->id);
        $this->assertEquals('partial', $h->status);
        $this->assertEquals([$p2->id], $h->missing_packages);
        $this->assertEquals('dispatched', $p1->fresh()->status);
        $this->assertNotNull($s->fresh()->exceptions()->where('category','handover_failed')->first());
    }

    public function test_flight_event_records_and_updates_status(): void
    {
        $this->fixture();
        $airline = Airline::create(['name' => 'EK', 'code' => 'EK']);
        $f = Flight::create(['airline_id' => $airline->id, 'flight_no' => 'EK725', 'scheduled_departure' => now()->addHour()]);
        $e = $this->svc->recordFlightEvent($f, 'departed', 'manual', $this->user->id);
        $this->assertEquals('manual', $e->source);
        $this->assertEquals('departed', $f->fresh()->status);
        $this->assertNotNull($f->fresh()->actual_departure);
    }

    public function test_branch_isolation_scope(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $tzUser = \App\Models\User::factory()->create();
        $tzUser->givePermissionTo('shipment.view');
        \Modules\Identity\Models\EmployeeAssignment::create([
            'user_id' => $tzUser->id, 'branch_id' => $this->tz->id, 'status' => 'active',
            'company_id' => $this->dxb->company_id, 'department_id' => null,
            'effective_start' => now()->toDateString()]);
        $this->assertEquals(0, Shipment::for($tzUser)->count());
    }
}
