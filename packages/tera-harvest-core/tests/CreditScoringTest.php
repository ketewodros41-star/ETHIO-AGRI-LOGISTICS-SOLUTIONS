<?php

namespace Fleetbase\TeraHarvest\Tests;

use Fleetbase\TeraHarvest\Models\FarmerCreditScore;
use Fleetbase\TeraHarvest\Models\CreditScoreHistory;
use Fleetbase\TeraHarvest\Services\FarmerCreditScoringService;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class CreditScoringTest extends TestCase
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

    public function test_credit_score_model_has_ulid(): void
    {
        $score = FarmerCreditScore::make([
            'farmer_id'  => Str::ulid(),
            'company_id' => Str::ulid(),
        ]);
        $score->save();

        $this->assertNotEmpty($score->id);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', strtoupper($score->id));
    }

    public function test_score_band_defaults_to_unrated(): void
    {
        $score = FarmerCreditScore::make([
            'farmer_id'  => Str::ulid(),
            'company_id' => Str::ulid(),
        ]);
        $score->save();

        $this->assertEquals('unrated', $score->score_band);
        $this->assertEquals(0, $score->score);
    }

    public function test_is_eligible_for_microfinance_only_for_silver_and_above(): void
    {
        foreach (['bronze', 'unrated'] as $band) {
            $score = new FarmerCreditScore(['score_band' => $band]);
            $this->assertFalse($score->isEligibleForMicrofinance(), "{$band} should not be eligible");
        }
        foreach (['silver', 'gold', 'platinum'] as $band) {
            $score = new FarmerCreditScore(['score_band' => $band]);
            $this->assertTrue($score->isEligibleForMicrofinance(), "{$band} should be eligible");
        }
    }

    public function test_component_total_sums_all_four_scores(): void
    {
        $score = new FarmerCreditScore([
            'delivery_reliability_score' => 20,
            'quality_consistency_score'  => 18,
            'volume_history_score'       => 15,
            'payment_behaviour_score'    => 25,
        ]);

        $this->assertEquals(78, $score->getComponentTotal());
    }

    public function test_credit_score_history_has_no_updated_at(): void
    {
        $history = new CreditScoreHistory();
        $this->assertFalse($history->timestamps);
    }

    public function test_unique_constraint_on_farmer_company_pair(): void
    {
        $farmerId  = Str::ulid();
        $companyId = Str::ulid();

        FarmerCreditScore::create([
            'farmer_id'  => $farmerId,
            'company_id' => $companyId,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        FarmerCreditScore::create([
            'farmer_id'  => $farmerId,
            'company_id' => $companyId,
        ]);
    }
}
