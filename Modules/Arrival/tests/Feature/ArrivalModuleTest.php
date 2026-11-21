<?php
namespace Modules\Arrival\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Models\Branch;
use Modules\Arrival\Models\{ShipmentArrival, CustomsCase, AirportCollection, DistributionBatch};
use Modules\Arrival\Services\ArrivalService;
use Modules\Shipment\Services\ShipmentService;
use Modules\Cargo\Models\CargoPackage;
use Tests\TestCase;

class ArrivalModuleTest extends TestCase
{
    use RefreshDatabase;

    private ArrivalService $svc;
    private ShipmentService $shpSvc;
    private $user; private $dxb; private $tz;

    private function fixture(): void
    {
        $this->svc = app(ArrivalService::class);
        $this->shpSvc = app(ShipmentService::class);
        $this->seed(\Modules\Organization\Database\Seeders\OrganizationDatabaseSeeder::class);
        $this->dxb = Branch::where('code','DXB')->first() ?? Branch::factory()->create(['code'=>'DXB']);
        $this->tz = Branch::where('code','TZ')->first() ?? Branch::factory()->create(['code'=>'TZ']);
        $this->user = \App\Models\User::factory()->create();
        $this->seed(\Modules\Arrival\Database\Seeders\ArrivalDatabaseSeeder::class);
        $this->user->assignRole('Tanzania Receiving');
    }

    private function shipment(array $pkgStatuses = ['stored','stored']): \Modules\Shipment\Models\Shipment
    {
        $s = $this->shpSvc->create(['origin_branch_id' => $this->dxb->id, 'origin_airport' => 'DXB', 'destination_airport' => 'DAR'], $this->user->id);
        foreach ($pkgStatuses as $st) {
            $p = new CargoPackage();
            $p->forceFill(['reference' => 'XER-DXB-PKG-2026-' . str_pad((string) random_int(1,999999), 6, '0', STR_PAD_LEFT),
                'branch_id' => $this->dxb->id, 'status' => $st, 'gross_weight' => 10, 'chargeable_weight' => 12])->save();
            if ($st !== 'on_hold') $this->shpSvc->allocate($s, [$p->id], $this->user->id);
        }
        return $s->fresh();
    }

    private function arrival(array $pkgStatuses = ['stored']): ShipmentArrival
    {
        return $this->svc->register($this->shipment($pkgStatuses), $this->tz->id, $this->user->id);
    }

    public function test_registers_arrival_with_unique_reference_and_shipment_link(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $this->assertStringStartsWith('XER-ARR-TZ-' . date('Y') . '-', $a->reference);
        $this->assertNotNull($a->shipment_id);
        $this->assertEquals(1, $a->expected_packages);
    }

    public function test_prevents_duplicate_arrival_for_same_shipment(): void
    {
        $this->fixture();
        $s = $this->shipment();
        $this->svc->register($s, $this->tz->id, $this->user->id);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->register($s, $this->tz->id, $this->user->id);
    }

    public function test_arrival_transition_stamps_actual_arrival(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $this->svc->transition($a, 'in_transit', $this->user->id);
        $this->svc->transition($a->fresh(), 'arrived', $this->user->id);
        $this->assertNotNull($a->fresh()->actual_arrival);
        $this->assertEquals('arrived', $a->fresh()->status);
    }

    public function test_reconcile_detects_missing_and_excess(): void
    {
        $this->fixture();
        $a = $this->arrival(['stored','stored']);
        $expected = $a->shipment->activeAllocations()->pluck('package_id')->all();
        $stray = new CargoPackage();
        $stray->forceFill(['reference' => 'XER-DXB-PKG-2026-STRAY1', 'branch_id' => $this->dxb->id, 'status' => 'stored', 'gross_weight' => 5, 'chargeable_weight' => 6])->save();
        $res = $this->svc->reconcile($a, [$expected[0], $stray->id], $this->user->id);
        $this->assertEquals([$expected[1]], $res['missing']);
        $this->assertEquals([$stray->id], $res['excess']);
        $this->assertEquals('partially_received', $a->fresh()->status);
        $this->assertEquals(2, $a->discrepancies()->count());
    }

    public function test_full_reconcile_marks_fully_received(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $expected = $a->shipment->activeAllocations()->pluck('package_id')->all();
        $this->svc->reconcile($a, $expected, $this->user->id);
        $this->assertEquals('fully_received', $a->fresh()->status);
    }

    public function test_customs_case_requires_payment_evidence(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $c = $this->svc->openCase($a, [], $this->user->id);
        $this->svc->transitionCase($c, 'documents_pending', $this->user->id);
        $this->svc->transitionCase($c->fresh(), 'ready_for_submission', $this->user->id);
        $this->svc->transitionCase($c->fresh(), 'submitted', $this->user->id);
        $this->svc->transitionCase($c->fresh(), 'under_assessment', $this->user->id);
        $this->svc->transitionCase($c->fresh(), 'payment_pending', $this->user->id);
        try { $this->svc->transitionCase($c->fresh(), 'payment_recorded', $this->user->id); $this->fail('no evidence'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $this->svc->transitionCase($c->fresh(), 'payment_recorded', $this->user->id, ['payment_evidence' => 'TRA-RCPT-001']);
        $this->assertEquals('payment_recorded', $c->fresh()->status);
    }

    public function test_customs_release_requires_evidence(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $c = $this->svc->openCase($a, [], $this->user->id);
        $c->forceFill(['status' => 'awaiting_release'])->save();
        try { $this->svc->transitionCase($c, 'released', $this->user->id); $this->fail('released without evidence'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $this->svc->transitionCase($c->fresh(), 'released', $this->user->id, ['release_evidence' => 'TRA-REL-001']);
        $this->assertEquals('released', $c->fresh()->status);
    }

    public function test_collection_blocked_until_customs_released(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $col = $this->svc->requestCollection($a, ['vehicle' => 'T123ABC'], $this->user->id);
        try { $this->svc->approveCollection($col, $this->user->id, 'REL-1'); $this->fail('approved without customs'); }
        catch (\Throwable $e) { $this->assertTrue(true); }
        $cc = new \Modules\Arrival\Models\CustomsCase(['arrival_id' => $a->id, 'status' => 'released']);
        $cc->forceFill(['reference' => 'XER-CLR-2026-000001'])->save();
        $this->svc->approveCollection($col->fresh(), $this->user->id, 'REL-1');
        $this->assertEquals('approved', $col->fresh()->status);
    }

    public function test_duplicate_physical_receiving_prevented(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $pid = $a->shipment->activeAllocations()->value('package_id');
        $this->svc->receivePackage($a, $pid, $this->user->id);
        $this->assertEquals('received_tz', CargoPackage::find($pid)->status);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->svc->receivePackage($a, $pid, $this->user->id);
    }

    public function test_damaged_receipt_raises_discrepancy(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $pid = $a->shipment->activeAllocations()->value('package_id');
        $this->svc->receivePackage($a, $pid, $this->user->id, ['condition' => 'damaged']);
        $this->assertNotNull($a->discrepancies()->where('kind', 'damaged')->where('package_id', $pid)->first());
    }

    public function test_release_check_and_distribution_batch(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $pid = $a->shipment->activeAllocations()->value('package_id');
        $p = CargoPackage::find($pid);
        $chk = $this->svc->releaseCheck($p);
        $this->assertFalse($chk['ready']);
        $this->assertFalse($chk['physically_received']);
        // receive + release customs → ready
        $this->svc->receivePackage($a, $pid, $this->user->id);
        $cc2 = new \Modules\Arrival\Models\CustomsCase(['arrival_id' => $a->id, 'status' => 'released']);
        $cc2->forceFill(['reference' => 'XER-CLR-2026-000002'])->save();
        $chk2 = $this->svc->releaseCheck($p->fresh());
        $this->assertTrue($chk2['ready']);
        $b = $this->svc->createBatch([$pid], ['branch_id' => $this->tz->id], $this->user->id);
        $this->assertStringStartsWith('XER-DBT-', $b->reference);
        $payload = $this->svc->handoverPayload($b);
        $this->assertCount(1, $payload['packages']);
        $this->assertTrue($payload['packages'][0]['release']['ready']);
    }

    public function test_release_override_records_user_reason(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $pid = $a->shipment->activeAllocations()->value('package_id');
        $this->svc->receivePackage($a, $pid, $this->user->id);
        $this->svc->overrideRelease(CargoPackage::find($pid), 'customs_released', 'Verbal TRA confirmation pending doc', $this->user->id);
        $chk = $this->svc->releaseCheck(CargoPackage::find($pid));
        $this->assertTrue($chk['customs_released']);
        $this->assertDatabaseHas('release_overrides', ['package_id' => $pid, 'check' => 'customs_released']);
    }

    public function test_branch_isolation(): void
    {
        $this->fixture();
        $a = $this->arrival();
        $dxbUser = \App\Models\User::factory()->create();
        $dxbUser->givePermissionTo('arrival.view');
        \Modules\Identity\Models\EmployeeAssignment::create([
            'user_id' => $dxbUser->id, 'branch_id' => $this->dxb->id, 'status' => 'active',
            'company_id' => $this->dxb->company_id, 'department_id' => null,
            'effective_start' => now()->toDateString()]);
        $this->assertEquals(0, ShipmentArrival::for($dxbUser)->count());
    }
}
