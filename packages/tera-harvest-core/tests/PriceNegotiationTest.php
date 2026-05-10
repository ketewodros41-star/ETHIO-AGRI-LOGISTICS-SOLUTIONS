<?php

namespace Fleetbase\TeraHarvest\Tests;

use Fleetbase\TeraHarvest\Models\PriceNegotiation;
use Fleetbase\TeraHarvest\Models\NegotiationTurn;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class PriceNegotiationTest extends TestCase
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

    private function makeNegotiation(string $askEtb = '100.00', string $offerEtb = '80.00'): PriceNegotiation
    {
        return PriceNegotiation::create([
            'company_id'        => Str::ulid(),
            'listing_id'        => Str::ulid(),
            'buyer_id'          => Str::ulid(),
            'seller_id'         => Str::ulid(),
            'initial_ask_etb'   => $askEtb,
            'current_offer_etb' => $offerEtb,
            'quantity_kg'       => '100.000',
            'status'            => 'active',
            'max_turns'         => 5,
            'current_turn'      => 0,
            'expires_at'        => now()->addHours(48),
        ]);
    }

    public function test_negotiation_midpoint_is_average(): void
    {
        $n = $this->makeNegotiation('100.00', '80.00');
        $this->assertEquals('90.00', $n->midpoint());
    }

    public function test_has_reached_max_turns_false_initially(): void
    {
        $n = $this->makeNegotiation();
        $this->assertFalse($n->hasReachedMaxTurns());
    }

    public function test_has_reached_max_turns_true_when_at_limit(): void
    {
        $n = $this->makeNegotiation();
        $n->update(['current_turn' => 5]);
        $this->assertTrue($n->hasReachedMaxTurns());
    }

    public function test_is_expired_false_for_future(): void
    {
        $n = $this->makeNegotiation();
        $this->assertFalse($n->isExpired());
    }

    public function test_is_expired_true_for_past(): void
    {
        $n = $this->makeNegotiation();
        $n->update(['expires_at' => now()->subHour()]);
        $this->assertTrue($n->isExpired());
    }

    public function test_negotiation_turn_has_no_updated_at(): void
    {
        $turn = new NegotiationTurn();
        $this->assertFalse($turn->timestamps);
    }

    public function test_turns_relationship_returns_sorted_turns(): void
    {
        $n = $this->makeNegotiation();
        NegotiationTurn::create(['negotiation_id' => $n->id, 'company_id' => $n->company_id, 'turn_number' => 2, 'actor' => 'seller', 'offered_price_etb' => '95.00']);
        NegotiationTurn::create(['negotiation_id' => $n->id, 'company_id' => $n->company_id, 'turn_number' => 1, 'actor' => 'buyer', 'offered_price_etb' => '80.00']);

        $turns = $n->turns;
        $this->assertEquals(1, $turns->first()->turn_number);
        $this->assertEquals(2, $turns->last()->turn_number);
    }
}
