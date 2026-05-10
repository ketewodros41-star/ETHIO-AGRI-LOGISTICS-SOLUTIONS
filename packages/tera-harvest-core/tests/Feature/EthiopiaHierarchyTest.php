<?php

namespace Fleetbase\TeraHarvest\Tests\Feature;

use Fleetbase\TeraHarvest\Models\EthiopiaKebele;
use Fleetbase\TeraHarvest\Models\EthiopiaLandmark;
use Fleetbase\TeraHarvest\Models\EthiopiaRegion;
use Fleetbase\TeraHarvest\Models\EthiopiaWoreda;
use Fleetbase\TeraHarvest\Models\EthiopiaZone;
use Fleetbase\TeraHarvest\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class EthiopiaHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private EthiopiaRegion $region;
    private EthiopiaZone   $zone;
    private EthiopiaWoreda $woreda;
    private EthiopiaKebele $kebele;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = EthiopiaRegion::create(['name_en' => 'Oromia', 'name_am' => 'ኦሮሚያ', 'code' => 'ET-OR']);
        $this->zone   = EthiopiaZone::create(['region_id' => $this->region->id, 'name_en' => 'Jimma', 'name_am' => 'ጅማ', 'code' => 'OR-JIM']);
        $this->woreda = EthiopiaWoreda::create(['zone_id' => $this->zone->id, 'name_en' => 'Jimma Geneti', 'name_am' => 'ጅማ ጀነቲ']);
        $this->kebele = EthiopiaKebele::create(['woreda_id' => $this->woreda->id, 'name_en' => 'Kebele 01', 'name_am' => 'ቀበሌ 01']);
    }

    public function test_region_has_ulid_primary_key(): void
    {
        $this->assertNotEmpty($this->region->id);
        $this->assertEquals(26, strlen($this->region->id)); // ULID length
    }

    public function test_region_zones_relationship(): void
    {
        $this->assertCount(1, $this->region->zones);
        $this->assertEquals('Jimma', $this->region->zones->first()->name_en);
    }

    public function test_zone_woredas_relationship(): void
    {
        $this->assertCount(1, $this->zone->woredas);
    }

    public function test_woreda_kebeles_relationship(): void
    {
        $this->assertCount(1, $this->woreda->kebeles);
    }

    public function test_landmark_nearby_scope(): void
    {
        EthiopiaLandmark::create([
            'uuid'      => (string) Str::uuid(),
            'name_en'   => 'Test Landmark',
            'name_am'   => 'ፈተና',
            'latitude'  => 9.0000,
            'longitude' => 38.7500,
            'kebele_id' => $this->kebele->id,
        ]);

        $results = EthiopiaLandmark::scopeNearby(
            EthiopiaLandmark::query(),
            9.0000,
            38.7500,
            5.0
        )->get();

        $this->assertCount(1, $results);
    }

    public function test_landmark_far_away_not_returned(): void
    {
        EthiopiaLandmark::create([
            'uuid'      => (string) Str::uuid(),
            'name_en'   => 'Far Landmark',
            'name_am'   => 'ሩቅ',
            'latitude'  => 13.5000, // Mekelle
            'longitude' => 39.4700,
        ]);

        $results = EthiopiaLandmark::scopeNearby(
            EthiopiaLandmark::query(),
            9.0000,
            38.7500,
            5.0
        )->get();

        $this->assertCount(0, $results);
    }

    public function test_kebele_belongs_to_woreda_zone_region(): void
    {
        $kebele = EthiopiaKebele::with('woreda.zone.region')->first();
        $this->assertEquals('Oromia', $kebele->woreda->zone->region->name_en);
    }
}
