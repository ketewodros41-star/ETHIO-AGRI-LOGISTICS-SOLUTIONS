<?php

namespace Fleetbase\TeraHarvest\Services;

use Fleetbase\TeraHarvest\Models\AggregationLot;
use Fleetbase\TeraHarvest\Models\AggregationLotContribution;
use Fleetbase\TeraHarvest\Models\PaymentWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkAggregationService
{
    private const PLATFORM_COMMISSION_PCT = '3.5';

    public function addContribution(
        AggregationLot $lot,
        string $farmerId,
        string $companyId,
        string $contributedKg,
        string $qualityGrade
    ): AggregationLotContribution {
        if ($lot->status !== 'open') {
            throw new \RuntimeException("Lot {$lot->lot_number} is not open for contributions.");
        }

        $remaining = $lot->remainingCapacityKg();
        if (bccomp($contributedKg, $remaining, 3) > 0) {
            throw new \RuntimeException("Contribution exceeds remaining lot capacity.");
        }

        return DB::transaction(function () use ($lot, $farmerId, $companyId, $contributedKg, $qualityGrade) {
            $contribution = AggregationLotContribution::create([
                'lot_id'         => $lot->id,
                'farmer_id'      => $farmerId,
                'company_id'     => $companyId,
                'contributed_kg' => $contributedKg,
                'quality_grade'  => $qualityGrade,
                'payout_status'  => 'pending',
            ]);

            $newCollected = bcadd((string) $lot->collected_kg, $contributedKg, 3);
            $lot->update(['collected_kg' => $newCollected]);

            if (bccomp($newCollected, (string) $lot->target_kg, 3) >= 0) {
                $lot->update(['status' => 'closed']);
            }

            return $contribution;
        });
    }

    public function settleLot(AggregationLot $lot, string $totalSaleEtb): void
    {
        if ($lot->status !== 'sold') {
            throw new \RuntimeException("Lot must be in 'sold' status to settle.");
        }

        DB::transaction(function () use ($lot, $totalSaleEtb) {
            $commissionEtb    = bcmul($totalSaleEtb, bcdiv(self::PLATFORM_COMMISSION_PCT, '100', 6), 2);
            $netFarmerTotal   = bcsub($totalSaleEtb, $commissionEtb, 2);

            $lot->update([
                'total_sale_etb'           => $totalSaleEtb,
                'platform_commission_etb'  => $commissionEtb,
                'net_farmer_payout_etb'    => $netFarmerTotal,
            ]);

            $contributions = AggregationLotContribution::where('lot_id', $lot->id)->get();
            $totalKg       = $contributions->sum(fn($c) => (float) $c->contributed_kg);

            if ($totalKg == 0.0) {
                return;
            }

            foreach ($contributions as $contribution) {
                $share        = bcdiv((string) $contribution->contributed_kg, (string) $totalKg, 8);
                $grossPayout  = bcmul($totalSaleEtb, $share, 2);
                $commission   = bcmul($commissionEtb, $share, 2);
                $netPayout    = bcsub($grossPayout, $commission, 2);
                $pricePerKg   = bccomp((string) $contribution->contributed_kg, '0', 3) === 0
                    ? '0.00'
                    : bcdiv($netPayout, (string) $contribution->contributed_kg, 2);

                $contribution->update([
                    'price_per_kg_etb' => $pricePerKg,
                    'gross_payout_etb' => $grossPayout,
                    'commission_etb'   => $commission,
                    'net_payout_etb'   => $netPayout,
                    'payout_status'    => 'processing',
                ]);

                $wallet = PaymentWallet::firstOrCreate(
                    ['owner_id' => $contribution->farmer_id, 'company_id' => $contribution->company_id],
                    ['balance' => '0.00', 'currency' => 'ETB']
                );
                $wallet->increment('balance', (float) $netPayout);
                $contribution->update(['payout_status' => 'paid', 'paid_at' => now()]);
            }

            $lot->update(['status' => 'settled', 'settled_at' => now()]);
        });
    }

    public function autoMatch(AggregationLot $lot): bool
    {
        if (bccomp((string) $lot->collected_kg, (string) $lot->target_kg, 3) < 0) {
            return false;
        }

        $lot->update(['status' => 'dispatched', 'dispatched_at' => now()]);
        Log::info("AggregationLot {$lot->lot_number} auto-dispatched.");
        return true;
    }
}
