<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\DriverEarningsSummary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateDriverEarningsSummary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        private string $driverId,
        private string $companyId,
        private string $periodType,
        private string $periodLabel
    ) {}

    public function handle(): void
    {
        [$year, $week, $month] = $this->parsePeriod();

        $deliveries = DB::table('orders')
                        ->where('driver_id', $this->driverId)
                        ->where('company_id', $this->companyId)
                        ->where('status', 'delivered')
                        ->when($this->periodType === 'weekly',
                            fn($q) => $q->whereRaw('YEARWEEK(delivered_at, 1) = ?', ["{$year}{$week}"]),
                            fn($q) => $q->whereRaw('YEAR(delivered_at) = ? AND MONTH(delivered_at) = ?', [$year, $month])
                        )
                        ->selectRaw("
                            COUNT(*) as total,
                            SUM(distance_km) as total_km,
                            AVG(driver_rating) as avg_rating,
                            SUM(CASE WHEN delivered_on_time = 1 THEN 1 ELSE 0 END) as on_time,
                            SUM(CASE WHEN delivered_on_time = 0 THEN 1 ELSE 0 END) as late
                        ")
                        ->first();

        $baseEarnings = bcmul((string) ($deliveries->total ?? 0), '85.00', 2);
        $bonus        = ($deliveries->total ?? 0) >= 30 ? '500.00' : '0.00';
        $net          = bcadd($baseEarnings, $bonus, 2);

        $summary = DriverEarningsSummary::updateOrCreate(
            [
                'driver_id'    => $this->driverId,
                'period_type'  => $this->periodType,
                'period_label' => $this->periodLabel,
            ],
            [
                'company_id'          => $this->companyId,
                'year'                => $year,
                'week'                => $week,
                'month'               => $month,
                'deliveries_completed' => $deliveries->total ?? 0,
                'total_distance_km'   => $deliveries->total_km ?? '0.000',
                'base_earnings_etb'   => $baseEarnings,
                'bonus_etb'           => $bonus,
                'deductions_etb'      => '0.00',
                'net_earnings_etb'    => $net,
                'avg_rating'          => $deliveries->avg_rating ?? '0.00',
                'on_time_deliveries'  => $deliveries->on_time ?? 0,
                'late_deliveries'     => $deliveries->late ?? 0,
                'badges_earned'       => $this->determineBadges($deliveries),
            ]
        );

        AwardDriverBadges::dispatch($summary->id);
        Log::info("DriverEarningsSummary generated for driver {$this->driverId} period {$this->periodLabel}");
    }

    private function parsePeriod(): array
    {
        if ($this->periodType === 'weekly') {
            [$year, $week] = explode('-W', $this->periodLabel);
            return [(int) $year, (int) $week, null];
        }
        [$year, $month] = explode('-', $this->periodLabel);
        return [(int) $year, null, (int) $month];
    }

    private function determineBadges(?\stdClass $deliveries): array
    {
        $badges = [];
        if (($deliveries->total ?? 0) >= 50) {
            $badges[] = 'high_performer';
        }
        if (($deliveries->avg_rating ?? 0) >= 4.8) {
            $badges[] = 'top_rated';
        }
        $onTimeRate = ($deliveries->total ?? 0) > 0
            ? (($deliveries->on_time ?? 0) / $deliveries->total) * 100
            : 0;
        if ($onTimeRate >= 95) {
            $badges[] = 'punctuality_star';
        }
        return $badges;
    }
}
