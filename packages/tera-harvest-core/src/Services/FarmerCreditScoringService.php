<?php

namespace Fleetbase\TeraHarvest\Services;

use Fleetbase\TeraHarvest\Models\FarmerCreditScore;
use Fleetbase\TeraHarvest\Models\CreditScoreHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FarmerCreditScoringService
{
    // Maximum points per component
    private const MAX_COMPONENT = 25;

    // Score band thresholds
    private const BANDS = [
        'platinum' => 90,
        'gold'     => 75,
        'silver'   => 55,
        'bronze'   => 30,
    ];

    public function recalculate(string $farmerId, string $companyId, string $triggeredByEvent = 'manual'): FarmerCreditScore
    {
        return DB::transaction(function () use ($farmerId, $companyId, $triggeredByEvent) {
            $score = FarmerCreditScore::firstOrCreate(
                ['farmer_id' => $farmerId, 'company_id' => $companyId],
                ['score' => 0, 'score_band' => 'unrated']
            );

            $oldScore = $score->score;

            $deliveryScore  = $this->calcDeliveryReliability($farmerId, $companyId);
            $qualityScore   = $this->calcQualityConsistency($farmerId, $companyId);
            $volumeScore    = $this->calcVolumeHistory($farmerId, $companyId);
            $paymentScore   = $this->calcPaymentBehaviour($farmerId, $companyId);

            $total = $deliveryScore + $qualityScore + $volumeScore + $paymentScore;
            $band  = $this->resolveBand($total);

            $stats = $this->fetchStats($farmerId, $companyId);

            $score->update([
                'score'                      => $total,
                'score_band'                 => $band,
                'delivery_reliability_score' => $deliveryScore,
                'quality_consistency_score'  => $qualityScore,
                'volume_history_score'       => $volumeScore,
                'payment_behaviour_score'    => $paymentScore,
                'total_orders_completed'     => $stats['total_orders'],
                'total_kg_traded'            => $stats['total_kg'],
                'avg_quality_grade'          => $stats['avg_grade'],
                'seasons_active'             => $stats['seasons_active'],
                'last_calculated_at'         => now(),
                'score_version'              => DB::raw('score_version + 1'),
            ]);

            CreditScoreHistory::create([
                'farmer_id'          => $farmerId,
                'company_id'         => $companyId,
                'score'              => $total,
                'score_band'         => $band,
                'delta'              => $total - $oldScore,
                'reason'             => "Recalculated via {$triggeredByEvent}",
                'triggered_by_event' => $triggeredByEvent,
                'calculated_at'      => now(),
            ]);

            return $score->fresh();
        });
    }

    private function calcDeliveryReliability(string $farmerId, string $companyId): int
    {
        $stats = DB::table('quality_grades')
            ->where('farmer_id', $farmerId)
            ->where('company_id', $companyId)
            ->selectRaw('COUNT(*) as total, SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as on_time')
            ->first();

        if (!$stats || $stats->total === 0) {
            return 0;
        }
        $rate = $stats->on_time / $stats->total;
        return (int) round($rate * self::MAX_COMPONENT);
    }

    private function calcQualityConsistency(string $farmerId, string $companyId): int
    {
        $avgGrade = DB::table('quality_grades')
            ->where('farmer_id', $farmerId)
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->selectRaw("AVG(CASE grade WHEN 'A' THEN 3 WHEN 'B' THEN 2 WHEN 'C' THEN 1 ELSE 0 END) as avg_score")
            ->value('avg_score');

        if ($avgGrade === null) {
            return 0;
        }
        return (int) round(($avgGrade / 3) * self::MAX_COMPONENT);
    }

    private function calcVolumeHistory(string $farmerId, string $companyId): int
    {
        $totalKg = DB::table('quality_grades')
            ->where('farmer_id', $farmerId)
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->sum('net_weight_kg');

        $benchmarkKg = 5000;
        $ratio       = min(1.0, (float) $totalKg / $benchmarkKg);
        return (int) round($ratio * self::MAX_COMPONENT);
    }

    private function calcPaymentBehaviour(string $farmerId, string $companyId): int
    {
        $disputes = DB::table('payment_transactions')
            ->where('company_id', $companyId)
            ->where('reference_id', $farmerId)
            ->where('status', 'disputed')
            ->where('created_at', '>=', now()->subMonths(12))
            ->count();

        if ($disputes === 0) {
            return self::MAX_COMPONENT;
        }
        return max(0, self::MAX_COMPONENT - ($disputes * 5));
    }

    private function fetchStats(string $farmerId, string $companyId): array
    {
        $row = DB::table('quality_grades')
            ->where('farmer_id', $farmerId)
            ->where('company_id', $companyId)
            ->where('status', 'approved')
            ->selectRaw("
                COUNT(*) as total_orders,
                COALESCE(SUM(net_weight_kg), 0) as total_kg,
                COALESCE(
                    CASE
                        WHEN AVG(CASE grade WHEN 'A' THEN 3 WHEN 'B' THEN 2 WHEN 'C' THEN 1 ELSE 0 END) >= 2.5 THEN 'A'
                        WHEN AVG(CASE grade WHEN 'A' THEN 3 WHEN 'B' THEN 2 WHEN 'C' THEN 1 ELSE 0 END) >= 1.5 THEN 'B'
                        ELSE 'C'
                    END,
                    'ungraded'
                ) as avg_grade
            ")
            ->first();

        $seasons = DB::table('quality_grades')
            ->where('farmer_id', $farmerId)
            ->where('company_id', $companyId)
            ->selectRaw("COUNT(DISTINCT YEAR(created_at)) as seasons")
            ->value('seasons');

        return [
            'total_orders'   => (int) ($row->total_orders ?? 0),
            'total_kg'       => (string) ($row->total_kg ?? '0.000'),
            'avg_grade'      => $row->avg_grade ?? 'ungraded',
            'seasons_active' => (int) ($seasons ?? 0),
        ];
    }

    private function resolveBand(int $score): string
    {
        foreach (self::BANDS as $band => $threshold) {
            if ($score >= $threshold) {
                return $band;
            }
        }
        return 'bronze';
    }

    public function buildAnonymisedPackage(FarmerCreditScore $score): array
    {
        return [
            'score_band'            => $score->score_band,
            'total_orders'          => $score->total_orders_completed,
            'seasons_active'        => $score->seasons_active,
            'avg_quality_grade'     => $score->avg_quality_grade,
            'delivery_reliability'  => $this->bandLabel($score->delivery_reliability_score),
            'quality_consistency'   => $this->bandLabel($score->quality_consistency_score),
            'volume_history'        => $this->bandLabel($score->volume_history_score),
            'payment_behaviour'     => $this->bandLabel($score->payment_behaviour_score),
            'eligible_for_credit'   => $score->isEligibleForMicrofinance(),
            'data_as_of'            => $score->last_calculated_at,
        ];
    }

    private function bandLabel(int $componentScore): string
    {
        if ($componentScore >= 22) return 'excellent';
        if ($componentScore >= 17) return 'good';
        if ($componentScore >= 10) return 'fair';
        return 'poor';
    }
}
