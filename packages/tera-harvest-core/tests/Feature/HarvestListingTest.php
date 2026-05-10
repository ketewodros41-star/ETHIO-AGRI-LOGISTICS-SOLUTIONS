<?php

namespace Fleetbase\TeraHarvest\Tests\Feature;

use Fleetbase\TeraHarvest\Models\HarvestListing;
use Fleetbase\TeraHarvest\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class HarvestListingTest extends TestCase
{
    use RefreshDatabase;

    public function test_harvest_listing_creates_with_ulid(): void
    {
        $listing = HarvestListing::create([
            'uuid'             => (string) Str::uuid(),
            'company_id'       => 'test-company',
            'crop_type'        => 'coffee',
            'quantity_kg'      => '500.00',
            'asking_price_etb' => '250.00',
            'status'           => 'active',
        ]);

        $this->assertNotEmpty($listing->id);
        $this->assertEquals(26, strlen($listing->id));
        $this->assertEquals('coffee', $listing->crop_type);
    }

    public function test_active_scope_filters_correctly(): void
    {
        HarvestListing::create([
            'uuid' => (string) Str::uuid(), 'company_id' => 'co1',
            'crop_type' => 'teff', 'quantity_kg' => '100', 'asking_price_etb' => '50', 'status' => 'active',
        ]);
        HarvestListing::create([
            'uuid' => (string) Str::uuid(), 'company_id' => 'co1',
            'crop_type' => 'wheat', 'quantity_kg' => '200', 'asking_price_etb' => '80', 'status' => 'expired',
        ]);

        $active = HarvestListing::active()->get();
        $this->assertCount(1, $active);
        $this->assertEquals('teff', $active->first()->crop_type);
    }

    public function test_soft_delete_does_not_hard_delete(): void
    {
        $listing = HarvestListing::create([
            'uuid' => (string) Str::uuid(), 'company_id' => 'co1',
            'crop_type' => 'maize', 'quantity_kg' => '300', 'asking_price_etb' => '60', 'status' => 'active',
        ]);

        $listing->delete();

        $this->assertSoftDeleted($listing);
        $this->assertDatabaseHas('harvest_listings', ['id' => $listing->id]);
    }

    public function test_asking_price_stored_as_decimal(): void
    {
        $listing = HarvestListing::create([
            'uuid' => (string) Str::uuid(), 'company_id' => 'co1',
            'crop_type' => 'sesame', 'quantity_kg' => '250', 'asking_price_etb' => '1234.56', 'status' => 'active',
        ]);

        $this->assertEquals('1234.56', $listing->fresh()->asking_price_etb);
    }
}
