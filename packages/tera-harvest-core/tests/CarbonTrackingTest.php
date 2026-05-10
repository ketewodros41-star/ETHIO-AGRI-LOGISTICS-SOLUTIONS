<?php

namespace Fleetbase\TeraHarvest\Tests;

use Fleetbase\TeraHarvest\Models\CarbonEmissionsLog;
use Fleetbase\TeraHarvest\Models\CarbonCreditReport;
use Fleetbase\TeraHarvest\Services\CarbonTrackingService;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class CarbonTrackingTest extends TestCase
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

    private function service(): CarbonTrackingService
    {
        return new CarbonTrackingService();
    }

    public function test_log_shipment_calculates_co2_from_diesel(): void
    {
        $companyId = Str::ulid();

        $log = $this->service()->logShipment(
            companyId: $companyId,
            orderId: Str::ulid(),
            driverId: Str::ulid(),
            vehicleId: Str::ulid(),
            vehicleType: 'truck',
            distanceKm: 100.0,
            cargoWeightKg: 5000.0,
            fuelType: 'diesel'
        );

        $this->assertGreaterThan(0, (float) $log->co2_kg);
        $this->assertEquals('diesel', $log->fuel_type);
        $this->assertEquals(100.0, (float) $log->distance_km);
        $this->assertEquals('2006', $log->ipcc_source_version);
    }

    public function test_electric_vehicle_has_zero_emissions(): void
    {
        $log = $this->service()->logShipment(
            companyId: Str::ulid(),
            orderId: Str::ulid(),
            driverId: Str::ulid(),
            vehicleId: Str::ulid(),
            vehicleType: 'van',
            distanceKm: 50.0,
            cargoWeightKg: 1000.0,
            fuelType: 'electric'
        );

        $this->assertEquals('0.000', $log->co2_kg);
        $this->assertEquals('0.000', $log->co2e_kg);
    }

    public function test_emission_log_has_no_timestamps(): void
    {
        $log = new CarbonEmissionsLog();
        $this->assertFalse($log->timestamps);
    }

    public function test_monthly_report_aggregates_correctly(): void
    {
        $companyId = Str::ulid();
        $year      = 2024;
        $month     = 3;

        // Log 2 shipments in March 2024
        foreach ([100.0, 200.0] as $distance) {
            CarbonEmissionsLog::create([
                'company_id'                    => $companyId,
                'order_id'                      => Str::ulid(),
                'activity_type'                 => 'road_transport',
                'fuel_type'                     => 'diesel',
                'distance_km'                   => $distance,
                'cargo_weight_kg'               => 1000.0,
                'emission_factor_kg_co2_per_km' => '0.06804',
                'co2_kg'                        => number_format($distance * 0.3 * 2.68, 3),
                'co2e_kg'                       => number_format($distance * 0.3 * 2.68, 3),
                'ipcc_source_version'           => '2006',
                'activity_date'                 => "2024-03-15 10:00:00",
            ]);
        }

        $report = $this->service()->generateMonthlyReport($companyId, $year, $month);

        $this->assertEquals($year, $report->year);
        $this->assertEquals($month, $report->month);
        $this->assertGreaterThan(0, (float) $report->total_co2e_kg);
        $this->assertEquals(300.0, (float) $report->total_distance_km);
    }

    public function test_report_unique_per_company_year_month(): void
    {
        $companyId = Str::ulid();

        CarbonCreditReport::create([
            'company_id'    => $companyId,
            'report_period' => '2024-01',
            'year'          => 2024,
            'month'         => 1,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        CarbonCreditReport::create([
            'company_id'    => $companyId,
            'report_period' => '2024-01',
            'year'          => 2024,
            'month'         => 1,
        ]);
    }
}
