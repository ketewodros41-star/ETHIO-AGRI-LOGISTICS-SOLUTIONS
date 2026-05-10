<?php

namespace Fleetbase\TeraHarvest\Services;

use Fleetbase\TeraHarvest\Models\CarbonEmissionsLog;
use Fleetbase\TeraHarvest\Models\CarbonCreditReport;
use Illuminate\Support\Facades\DB;

class CarbonTrackingService
{
    // IPCC 2006 emission factors kg CO2e per litre by fuel type
    private const EMISSION_FACTORS_L = [
        'diesel'   => 2.68,
        'petrol'   => 2.31,
        'lpg'      => 1.61,
        'electric' => 0.00,
    ];

    // Fuel consumption litres per km by vehicle type (approximate)
    private const FUEL_EFFICIENCY = [
        'truck'       => 0.30,
        'van'         => 0.12,
        'motorcycle'  => 0.05,
        'default'     => 0.20,
    ];

    public function logShipment(
        string $companyId,
        string $orderId,
        string $driverId,
        string $vehicleId,
        string $vehicleType,
        float  $distanceKm,
        float  $cargoWeightKg,
        string $fuelType = 'diesel'
    ): CarbonEmissionsLog {
        $efficiency       = self::FUEL_EFFICIENCY[$vehicleType] ?? self::FUEL_EFFICIENCY['default'];
        $fuelLitres       = round($distanceKm * $efficiency, 3);
        $emissionFactor   = self::EMISSION_FACTORS_L[$fuelType] ?? self::EMISSION_FACTORS_L['diesel'];
        $co2Kg            = round($fuelLitres * $emissionFactor, 3);
        $co2eKg           = $co2Kg;

        $factorPerKm      = $distanceKm > 0 ? round($co2Kg / $distanceKm, 5) : 0.0;

        return CarbonEmissionsLog::create([
            'company_id'                    => $companyId,
            'order_id'                      => $orderId,
            'driver_id'                     => $driverId,
            'vehicle_id'                    => $vehicleId,
            'activity_type'                 => 'road_transport',
            'fuel_type'                     => $fuelType,
            'distance_km'                   => $distanceKm,
            'fuel_consumed_litres'          => $fuelLitres,
            'cargo_weight_kg'               => $cargoWeightKg,
            'emission_factor_kg_co2_per_km' => $factorPerKm,
            'co2_kg'                        => $co2Kg,
            'co2e_kg'                       => $co2eKg,
            'ipcc_source_version'           => '2006',
            'activity_date'                 => now(),
        ]);
    }

    public function generateMonthlyReport(string $companyId, int $year, int $month): CarbonCreditReport
    {
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate   = date('Y-m-t', strtotime($startDate));

        $summary = DB::table('carbon_emissions_log')
            ->where('company_id', $companyId)
            ->whereBetween('activity_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw("
                SUM(co2e_kg) as total_co2e_kg,
                SUM(distance_km) as total_distance_km,
                COUNT(DISTINCT order_id) as total_orders
            ")
            ->first();

        $totalCo2e    = (string) ($summary->total_co2e_kg ?? '0.000');
        $totalDistKm  = (string) ($summary->total_distance_km ?? '0.000');
        $totalOrders  = (int) ($summary->total_orders ?? 0);

        $co2ePerOrder = $totalOrders > 0
            ? bcdiv($totalCo2e, (string) $totalOrders, 3)
            : '0.000';

        $co2ePerKm = bccomp($totalDistKm, '0', 3) > 0
            ? bcdiv($totalCo2e, $totalDistKm, 5)
            : '0.00000';

        $benchmark    = config('tera_harvest.carbon.industry_benchmark_kg_per_month');
        $variancePct  = null;
        if ($benchmark && $benchmark > 0) {
            $variancePct = bcdiv(
                bcmul(bcsub($totalCo2e, (string) $benchmark, 3), '100', 3),
                (string) $benchmark,
                2
            );
        }

        $breakdownByFuel = DB::table('carbon_emissions_log')
            ->where('company_id', $companyId)
            ->whereBetween('activity_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->selectRaw('fuel_type, SUM(co2e_kg) as total_co2e_kg, COUNT(*) as trips')
            ->groupBy('fuel_type')
            ->get()
            ->keyBy('fuel_type')
            ->toArray();

        $report = CarbonCreditReport::updateOrCreate(
            ['company_id' => $companyId, 'year' => $year, 'month' => $month],
            [
                'report_period'          => sprintf('%04d-%02d', $year, $month),
                'total_co2e_kg'          => $totalCo2e,
                'total_distance_km'      => $totalDistKm,
                'total_orders'           => $totalOrders,
                'co2e_per_order_kg'      => $co2ePerOrder,
                'co2e_per_km_kg'         => $co2ePerKm,
                'industry_benchmark_kg'  => $benchmark,
                'variance_pct'           => $variancePct,
                'breakdown_by_fuel_type' => $breakdownByFuel,
                'generated_at'           => now(),
            ]
        );

        return $report;
    }
}
