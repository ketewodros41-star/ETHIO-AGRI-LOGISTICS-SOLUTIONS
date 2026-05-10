<?php

namespace Fleetbase\TeraHarvest\Tests;

use Fleetbase\TeraHarvest\Models\ForwardContract;
use Fleetbase\TeraHarvest\Services\ForwardContractService;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ForwardContractTest extends TestCase
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

    private function service(): ForwardContractService
    {
        return new ForwardContractService();
    }

    public function test_contract_creation_computes_total_and_deposit(): void
    {
        $contract = $this->service()->create([
            'company_id'           => Str::ulid(),
            'buyer_id'             => Str::ulid(),
            'commodity'            => 'coffee',
            'quantity_kg'          => '1000.000',
            'price_per_kg_etb'     => '50.00',
            'delivery_start_date'  => '2024-11-01',
            'delivery_end_date'    => '2024-12-31',
        ]);

        $this->assertEquals('50000.00', $contract->total_value_etb);
        $this->assertEquals('10.00', $contract->deposit_pct);
        $this->assertEquals('5000.00', $contract->deposit_etb);
        $this->assertEquals('draft', $contract->status);
    }

    public function test_contract_number_is_generated(): void
    {
        $contract = $this->service()->create([
            'company_id'          => Str::ulid(),
            'buyer_id'            => Str::ulid(),
            'commodity'           => 'teff',
            'quantity_kg'         => '500.000',
            'price_per_kg_etb'    => '30.00',
            'delivery_start_date' => '2024-11-01',
            'delivery_end_date'   => '2024-12-31',
        ]);

        $this->assertStringStartsWith('FWD-', $contract->contract_number);
    }

    public function test_remaining_kg_decreases_on_fulfilment(): void
    {
        $contract = ForwardContract::create([
            'contract_number'     => 'FWD-TEST',
            'company_id'          => Str::ulid(),
            'buyer_id'            => Str::ulid(),
            'commodity'           => 'coffee',
            'quantity_kg'         => '1000.000',
            'price_per_kg_etb'    => '50.00',
            'total_value_etb'     => '50000.00',
            'deposit_pct'         => '10.00',
            'deposit_etb'         => '5000.00',
            'status'              => 'active',
            'fulfilled_kg'        => '0.000',
            'delivery_start_date' => '2024-11-01',
            'delivery_end_date'   => '2024-12-31',
        ]);

        $this->assertEquals('1000.000', $contract->remainingKg());

        $this->service()->recordFulfilment($contract, [
            'quantity_kg'        => '300.000',
            'quality_grade'      => 'A',
            'price_per_kg_etb'   => '50.00',
            'company_id'         => $contract->company_id,
        ]);

        $contract->refresh();
        $this->assertEquals('700.000', $contract->remainingKg());
        $this->assertEquals('partially_fulfilled', $contract->status);
    }

    public function test_cancel_with_refund_restores_deposit_status(): void
    {
        $contract = ForwardContract::create([
            'contract_number'     => 'FWD-CANCEL',
            'company_id'          => Str::ulid(),
            'buyer_id'            => Str::ulid(),
            'commodity'           => 'sesame',
            'quantity_kg'         => '200.000',
            'price_per_kg_etb'    => '80.00',
            'total_value_etb'     => '16000.00',
            'deposit_pct'         => '10.00',
            'deposit_etb'         => '1600.00',
            'deposit_status'      => 'paid',
            'status'              => 'active',
            'fulfilled_kg'        => '0.000',
            'delivery_start_date' => '2024-11-01',
            'delivery_end_date'   => '2024-12-31',
        ]);

        $this->service()->cancel($contract, 'Buyer changed mind', refundDeposit: false);

        $contract->refresh();
        $this->assertEquals('cancelled', $contract->status);
        $this->assertEquals('forfeited', $contract->deposit_status);
    }
}
