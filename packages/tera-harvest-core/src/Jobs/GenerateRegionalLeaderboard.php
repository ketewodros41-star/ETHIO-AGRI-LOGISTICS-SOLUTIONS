<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\DriverEarningsSummary;
use Fleetbase\TeraHarvest\Models\DriverLeaderboard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateRegionalLeaderboard implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        private string $companyId,
        private string $periodType,
        private string $periodLabel,
        private ?string $regionId = null
    ) {}

    public function handle(): void
    {
        $summaries = DriverEarningsSummary::where('company_id', $this->companyId)
                                           ->where('period_type', $this->periodType)
                                           ->where('period_label', $this->periodLabel)
                                           ->get()
                                           ->sortByDesc(fn($s) => $s->performanceScore());

        DriverLeaderboard::where('company_id', $this->companyId)
                          ->where('period_type', $this->periodType)
                          ->where('period_label', $this->periodLabel)
                          ->when($this->regionId, fn($q) => $q->where('region_id', $this->regionId))
                          ->delete();

        $rank = 1;
        foreach ($summaries as $summary) {
            $displayName = DB::table('users')->where('id', $summary->driver_id)->value('name') ?? "Driver";

            DriverLeaderboard::create([
                'company_id'           => $this->companyId,
                'period_type'          => $this->periodType,
                'period_label'         => $this->periodLabel,
                'region_id'            => $this->regionId,
                'rank'                 => $rank,
                'driver_id'            => $summary->driver_id,
                'display_name'         => $rank <= 3 ? $displayName : "Driver #{$rank}",
                'deliveries_completed' => $summary->deliveries_completed,
                'total_distance_km'    => $summary->total_distance_km,
                'net_earnings_etb'     => $summary->net_earnings_etb,
                'avg_rating'           => $summary->avg_rating,
                'on_time_rate_pct'     => $summary->onTimeRate(),
                'badges'               => $summary->badges_earned,
                'score'                => $summary->performanceScore(),
            ]);
            $rank++;
        }
    }
}
