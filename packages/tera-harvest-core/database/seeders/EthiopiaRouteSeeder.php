<?php

namespace Fleetbase\TeraHarvest\Database\Seeders;

use Fleetbase\TeraHarvest\Models\EthiopiaWoreda;
use Fleetbase\TeraHarvest\Models\RouteSegment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EthiopiaRouteSeeder extends Seeder
{
    /**
     * Major agricultural corridors:
     * Addis→Jimma, Addis→Gondar, Addis→Hawassa, Addis→Adama, Addis→Dire Dawa
     *
     * Seeded as representative segments (road-level data to be populated by drivers).
     */
    public function run(): void
    {
        $corridors = [
            ['origin' => 'Addis Ababa', 'dest' => 'Adama',    'distance_km' => 99,  'road_type' => 'paved', 'avg_transit_hours' => 1.5],
            ['origin' => 'Addis Ababa', 'dest' => 'Hawassa',  'distance_km' => 275, 'road_type' => 'paved', 'avg_transit_hours' => 4.0],
            ['origin' => 'Addis Ababa', 'dest' => 'Jimma',    'distance_km' => 346, 'road_type' => 'paved', 'avg_transit_hours' => 5.0],
            ['origin' => 'Addis Ababa', 'dest' => 'Dire Dawa','distance_km' => 525, 'road_type' => 'paved', 'avg_transit_hours' => 7.5],
            ['origin' => 'Addis Ababa', 'dest' => 'Gondar',   'distance_km' => 738, 'road_type' => 'paved', 'avg_transit_hours' => 10.0],
        ];

        foreach ($corridors as $corridor) {
            $origin = EthiopiaWoreda::whereRaw('LOWER(name_en) LIKE ?', ['%' . strtolower($corridor['origin']) . '%'])->first();
            $dest   = EthiopiaWoreda::whereRaw('LOWER(name_en) LIKE ?', ['%' . strtolower($corridor['dest']) . '%'])->first();

            if ($origin && $dest) {
                RouteSegment::firstOrCreate(
                    ['origin_woreda_id' => $origin->id, 'destination_woreda_id' => $dest->id],
                    [
                        'uuid'                  => (string) Str::uuid(),
                        'distance_km'           => $corridor['distance_km'],
                        'road_type'             => $corridor['road_type'],
                        'condition_score'       => 7,
                        'avg_transit_hours'     => $corridor['avg_transit_hours'],
                        'truck_accessible'      => true,
                        'rainy_season_passable' => true,
                        'last_verified_at'      => now(),
                    ]
                );
            }
        }
    }
}
