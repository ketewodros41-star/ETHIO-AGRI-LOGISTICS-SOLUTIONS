<?php

namespace Fleetbase\TeraHarvest\Tests;

use Fleetbase\TeraHarvest\Models\GovernmentApiKey;
use Orchestra\Testbench\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class GovernmentApiTest extends TestCase
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

    public function test_generate_key_returns_prefix_and_hash(): void
    {
        $keyData = GovernmentApiKey::generateKey();

        $this->assertArrayHasKey('raw', $keyData);
        $this->assertArrayHasKey('prefix', $keyData);
        $this->assertArrayHasKey('hash', $keyData);
        $this->assertEquals(8, strlen($keyData['prefix']));
        $this->assertEquals(64, strlen($keyData['hash']));
        $this->assertStringStartsWith('gov_', $keyData['raw']);
    }

    public function test_verify_key_matches_stored_hash(): void
    {
        $keyData = GovernmentApiKey::generateKey();
        $apiKey  = GovernmentApiKey::create([
            'company_id'    => Str::ulid(),
            'ministry_name' => 'Ministry of Agriculture',
            'key_prefix'    => $keyData['prefix'],
            'key_hash'      => $keyData['hash'],
        ]);

        $this->assertTrue($apiKey->verifyKey($keyData['raw']));
        $this->assertFalse($apiKey->verifyKey('wrong_key_12345'));
    }

    public function test_raw_key_is_hidden_from_serialisation(): void
    {
        $keyData = GovernmentApiKey::generateKey();
        $apiKey  = GovernmentApiKey::create([
            'company_id'    => Str::ulid(),
            'ministry_name' => 'Ministry of Trade',
            'key_prefix'    => $keyData['prefix'],
            'key_hash'      => $keyData['hash'],
        ]);

        $serialised = $apiKey->toArray();
        $this->assertArrayNotHasKey('key_hash', $serialised);
    }

    public function test_scope_check_allows_all_when_scopes_empty(): void
    {
        $apiKey = new GovernmentApiKey(['scopes' => null]);
        $this->assertTrue($apiKey->hasScope('any_scope'));
        $this->assertTrue($apiKey->hasScope('supply-overview'));
    }

    public function test_scope_check_restricts_when_scopes_set(): void
    {
        $apiKey = new GovernmentApiKey(['scopes' => ['supply-overview', 'farmer-stats']]);
        $this->assertTrue($apiKey->hasScope('supply-overview'));
        $this->assertFalse($apiKey->hasScope('carbon-summary'));
    }

    public function test_expired_key_returns_true_for_past_date(): void
    {
        $apiKey = new GovernmentApiKey(['expires_at' => now()->subDay()]);
        $this->assertTrue($apiKey->isExpired());
    }

    public function test_non_expired_key_returns_false(): void
    {
        $apiKey = new GovernmentApiKey(['expires_at' => now()->addYear()]);
        $this->assertFalse($apiKey->isExpired());
    }
}
