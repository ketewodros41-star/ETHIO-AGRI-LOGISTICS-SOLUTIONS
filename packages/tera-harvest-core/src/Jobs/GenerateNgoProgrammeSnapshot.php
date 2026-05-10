<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\NgoProgramme;
use Fleetbase\TeraHarvest\Models\NgoImpactSnapshot;
use Fleetbase\TeraHarvest\Models\NgoBeneficiary;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateNgoProgrammeSnapshot implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $programmeId) {}

    public function handle(): void
    {
        $programme = NgoProgramme::find($this->programmeId);
        if (!$programme) {
            return;
        }

        $beneficiaries = NgoBeneficiary::where('programme_id', $this->programmeId)->get();
        $active        = $beneficiaries->where('status', 'active')->count();
        $graduated     = $beneficiaries->where('status', 'graduated')->count();
        $women         = $beneficiaries->filter(fn($b) => ($b->baseline_data['gender'] ?? '') === 'female')->count();
        $youth         = $beneficiaries->filter(fn($b) => ($b->baseline_data['age'] ?? 99) <= 35)->count();

        $incomeChanges = $beneficiaries->filter(fn($b) => $b->income_baseline_etb && $b->income_current_etb)
                                        ->map(fn($b) => $b->incomeChangePercentage())
                                        ->filter();

        $avgIncomeChange = $incomeChanges->isNotEmpty()
            ? round($incomeChanges->average(), 2)
            : 0.0;

        $farmerIds = $beneficiaries->pluck('farmer_id')->toArray();
        $transactions = DB::table('payment_transactions')
                          ->whereIn('reference_id', $farmerIds)
                          ->where('type', 'credit')
                          ->whereBetween('created_at', [$programme->start_date, now()])
                          ->sum('amount');

        $totalKg = DB::table('quality_grades')
                     ->whereIn('farmer_id', $farmerIds)
                     ->where('status', 'approved')
                     ->sum('net_weight_kg');

        $period = now()->format('Y-m');

        NgoImpactSnapshot::create([
            'programme_id'           => $this->programmeId,
            'company_id'             => $programme->company_id,
            'snapshot_period'        => $period,
            'active_beneficiaries'   => $active,
            'graduated_beneficiaries' => $graduated,
            'avg_income_change_pct'  => $avgIncomeChange,
            'total_transactions_etb' => $transactions,
            'total_kg_traded'        => $totalKg,
            'women_beneficiaries'    => $women,
            'youth_beneficiaries'    => $youth,
        ]);

        Log::info("NgoImpactSnapshot generated for programme {$this->programmeId} period {$period}");
    }
}
