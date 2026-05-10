<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\RouteSegment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateRouteConditionScore implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly string $segmentId) {}

    public function handle(): void
    {
        $segment = RouteSegment::with('reports')->find($this->segmentId);
        if (!$segment) {
            return;
        }

        $recentReports = $segment->reports()
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        if ($recentReports->isEmpty()) {
            return;
        }

        // Map severity to numeric score
        $severityMap = ['low' => 8, 'medium' => 5, 'high' => 3, 'impassable' => 1];
        $scores = $recentReports->map(fn ($r) => $severityMap[$r->severity] ?? 5);

        $avgScore = (int) round($scores->average());

        $segment->update([
            'condition_score'   => max(1, min(10, $avgScore)),
            'last_verified_at'  => now(),
        ]);
    }
}
