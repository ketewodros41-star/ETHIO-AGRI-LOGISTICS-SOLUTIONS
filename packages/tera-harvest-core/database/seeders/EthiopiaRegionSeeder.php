<?php

namespace Fleetbase\TeraHarvest\Database\Seeders;

use Fleetbase\TeraHarvest\Models\EthiopiaRegion;
use Fleetbase\TeraHarvest\Models\EthiopiaZone;
use Illuminate\Database\Seeder;

class EthiopiaRegionSeeder extends Seeder
{
    public function run(): void
    {
        $regions = [
            ['code' => 'ET-AA', 'name_en' => 'Addis Ababa',           'name_am' => 'አዲስ አበባ'],
            ['code' => 'ET-AF', 'name_en' => 'Afar',                  'name_am' => 'አፋር'],
            ['code' => 'ET-AM', 'name_en' => 'Amhara',                'name_am' => 'አማራ'],
            ['code' => 'ET-BE', 'name_en' => 'Benishangul-Gumuz',     'name_am' => 'ቤኒሻንጉል-ጉምዝ'],
            ['code' => 'ET-DD', 'name_en' => 'Dire Dawa',             'name_am' => 'ድሬ ዳዋ'],
            ['code' => 'ET-GA', 'name_en' => 'Gambela',               'name_am' => 'ጋምቤላ'],
            ['code' => 'ET-HA', 'name_en' => 'Harari',                'name_am' => 'ሐረሪ'],
            ['code' => 'ET-OR', 'name_en' => 'Oromia',                'name_am' => 'ኦሮሚያ'],
            ['code' => 'ET-SI', 'name_en' => 'SNNPR',                 'name_am' => 'ደቡብ ብሔሮች፣ ብሔረሰቦችና ሕዝቦች'],
            ['code' => 'ET-SO', 'name_en' => 'Somali',                'name_am' => 'ሶማሊ'],
            ['code' => 'ET-TI', 'name_en' => 'Tigray',                'name_am' => 'ትግራይ'],
            ['code' => 'ET-SW', 'name_en' => 'South West Ethiopia',   'name_am' => 'ደቡብ ምዕራብ ኢትዮጵያ'],
        ];

        foreach ($regions as $region) {
            EthiopiaRegion::firstOrCreate(['code' => $region['code']], $region);
        }

        // Seed key zones for Oromia (largest region, main agricultural belt)
        $oromia = EthiopiaRegion::where('code', 'ET-OR')->first();
        if ($oromia) {
            $zones = [
                ['code' => 'OR-JIM', 'name_en' => 'Jimma',           'name_am' => 'ጅማ'],
                ['code' => 'OR-ARS', 'name_en' => 'Arsi',            'name_am' => 'አርሲ'],
                ['code' => 'OR-WGA', 'name_en' => 'West Guji',       'name_am' => 'ምዕራብ ጉጂ'],
                ['code' => 'OR-BOA', 'name_en' => 'Borena',          'name_am' => 'ቦረና'],
                ['code' => 'OR-EHR', 'name_en' => 'East Hararghe',   'name_am' => 'ምስራቅ ሐረርጌ'],
                ['code' => 'OR-WHA', 'name_en' => 'West Hararghe',   'name_am' => 'ምዕራብ ሐረርጌ'],
                ['code' => 'OR-ILU', 'name_en' => 'Illubabor',       'name_am' => 'ኢሉባቡር'],
                ['code' => 'OR-KAM', 'name_en' => 'Kelem Wellega',   'name_am' => 'ከለም ወለጋ'],
            ];
            foreach ($zones as $zone) {
                EthiopiaZone::firstOrCreate(
                    ['code' => $zone['code']],
                    array_merge($zone, ['region_id' => $oromia->id])
                );
            }
        }

        // Seed key zones for Amhara
        $amhara = EthiopiaRegion::where('code', 'ET-AM')->first();
        if ($amhara) {
            $zones = [
                ['code' => 'AM-GON', 'name_en' => 'North Gondar',    'name_am' => 'ሰሜን ጎንደር'],
                ['code' => 'AM-SCG', 'name_en' => 'South Gondar',    'name_am' => 'ደቡብ ጎንደር'],
                ['code' => 'AM-EGO', 'name_en' => 'East Gojjam',     'name_am' => 'ምስራቅ ጎጃም'],
                ['code' => 'AM-WGO', 'name_en' => 'West Gojjam',     'name_am' => 'ምዕራብ ጎጃም'],
                ['code' => 'AM-NOW', 'name_en' => 'North Wollo',     'name_am' => 'ሰሜን ወሎ'],
                ['code' => 'AM-SOW', 'name_en' => 'South Wollo',     'name_am' => 'ደቡብ ወሎ'],
            ];
            foreach ($zones as $zone) {
                EthiopiaZone::firstOrCreate(
                    ['code' => $zone['code']],
                    array_merge($zone, ['region_id' => $amhara->id])
                );
            }
        }
    }
}
