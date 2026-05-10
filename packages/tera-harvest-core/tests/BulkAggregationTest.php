<?php

namespace Fleetbase\TeraHarvest\Tests;

use Fleetbase\TeraHarvest\Models\AggregationLot;
use Fleetbase\TeraHarvest\Models\AggregationLotContribution;
use Fleetbase\TeraHarvest\Services\BulkAggregationService;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class BulkAggregationTest extends TestCase
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [\Fleetbase\TeraHarvest\Providers\TeraHarvestServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    private function makeLot(string $targetKg = '500.000'): AggregationLot
    {
        return AggregationLot::create([
            'company_id'  => Str::ulid(),
            'lot_number'  => 'LOT-TEST-001',
            'commodity'   => 'coffee',
            'target_kg'   => $targetKg,
            'status'      => 'open',
        ]);
    }

    public function test_lot_has_correct_remaining_capacity(): void
    {
        $lot = $this->makeLot('500.000');
        $lot->update(['collected_kg' => '200.000']);

        $this->assertEquals('300.000', $lot->remainingCapacityKg());
    }

    public function test_fill_percentage_is_correct(): void
    {
        $lot = $this->makeLot('500.000');
        $lot->update(['collected_kg' => '250.000']);

        $this->assertEquals(50.0, $lot->fillPercentage());
    }

    public function test_add_contribution_reduces_remaining_capacity(): void
    {
        $service    = new BulkAggregationService();
        $lot        = $this->makeLot('100.000');
        $companyId  = $lot->company_id;
        $farmerId   = Str::ulid();

        $service->addContribution($lot, $farmerId, $companyId, '30.000', 'A');

        $lot->refresh();
        $this->assertEquals('30.000', $lot->collected_kg);
        $this->assertEquals('open', $lot->status);
    }

    public function test_lot_closes_when_target_reached(): void
    {
        $service    = new BulkAggregationService();
        $lot        = $this->makeLot('50.000');
        $companyId  = $lot->company_id;

        $service->addContribution($lot, Str::ulid(), $companyId, '50.000', 'B');

        $lot->refresh();
        $this->assertEquals('closed', $lot->status);
    }

    public function test_contribution_exceeding_capacity_throws(): void
    {
        $service = new BulkAggregationService();
        $lot     = $this->makeLot('10.000');

        $this->expectException(\RuntimeException::class);
        $service->addContribution($lot, Str::ulid(), $lot->company_id, '20.000', 'A');
    }

    public function test_pro_rata_payout_uses_bcmath(): void
    {
        $service   = new BulkAggregationService();
        $lot       = $this->makeLot('300.000');
        $companyId = $lot->company_id;

        $farmer1 = Str::ulid();
        $farmer2 = Str::ulid();

        $service->addContribution($lot, $farmer1, $companyId, '200.000', 'A');
        $service->addContribution($lot, $farmer2, $companyId, '100.000', 'B');

        $lot->update(['status' => 'sold', 'sold_at' => now()]);
        $service->settleLot($lot, '3000.00');

        $c1 = AggregationLotContribution::where('farmer_id', $farmer1)->first();
        $c2 = AggregationLotContribution::where('farmer_id', $farmer2)->first();

        $this->assertNotNull($c1->net_payout_etb);
        $this->assertNotNull($c2->net_payout_etb);
        // Farmer1 gets ~2x farmer2's payout (200:100 ratio)
        $ratio = (float) $c1->net_payout_etb / (float) $c2->net_payout_etb;
        $this->assertEqualsWithDelta(2.0, $ratio, 0.05);
    }
}
