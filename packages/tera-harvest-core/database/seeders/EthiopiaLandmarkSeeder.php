<?php

namespace Fleetbase\TeraHarvest\Database\Seeders;

use Fleetbase\TeraHarvest\Models\EthiopiaLandmark;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EthiopiaLandmarkSeeder extends Seeder
{
    public function run(): void
    {
        $landmarks = [
            // Addis Ababa
            ['name_en' => 'Bole Medhanealem Church',     'name_am' => 'ቦሌ መድኃኒዓለም ቤ/ክ',         'latitude' => 8.9936,  'longitude' => 38.8025, 'description' => 'Major landmark in Bole area'],
            ['name_en' => 'Mercato Market',              'name_am' => 'መርካቶ ገበያ',                  'latitude' => 9.0252,  'longitude' => 38.7457, 'description' => "Africa's largest open-air market"],
            ['name_en' => 'Bole International Airport', 'name_am' => 'ቦሌ ዓለም አቀፍ አውሮፕላን ማረፊያ',  'latitude' => 8.9779,  'longitude' => 38.7993, 'description' => 'Main international airport'],
            ['name_en' => 'Meskel Square',               'name_am' => 'መስቀል አደባባይ',                'latitude' => 9.0168,  'longitude' => 38.7614, 'description' => 'Central square in Addis Ababa'],
            ['name_en' => 'Addis Ababa Train Station',  'name_am' => 'የባቡር ጣቢያ',                  'latitude' => 9.0267,  'longitude' => 38.7562, 'description' => 'Light rail station downtown'],
            // Dire Dawa
            ['name_en' => 'Dire Dawa Market',           'name_am' => 'ድሬ ዳዋ ገበያ',                'latitude' => 9.5900,  'longitude' => 41.8612, 'description' => 'Main market in Dire Dawa'],
            ['name_en' => 'Dire Dawa Airport',          'name_am' => 'ድሬ ዳዋ ኤርፖርት',              'latitude' => 9.6247,  'longitude' => 41.8542, 'description' => 'Dire Dawa domestic airport'],
            // Jimma
            ['name_en' => 'Jimma Market',               'name_am' => 'ጅማ ገበያ',                    'latitude' => 7.6730,  'longitude' => 36.8342, 'description' => 'Central market, major coffee trading hub'],
            ['name_en' => 'Jimma University',           'name_am' => 'ጅማ ዩኒቨርሲቲ',                'latitude' => 7.6697,  'longitude' => 36.8346, 'description' => 'Landmark near main road'],
            // Hawassa
            ['name_en' => 'Hawassa Lake Market',        'name_am' => 'ሀዋሳ ሃይቅ ገበያ',               'latitude' => 7.0621,  'longitude' => 38.4769, 'description' => 'Fish market by Lake Hawassa'],
            ['name_en' => 'Hawassa Industrial Park',    'name_am' => 'ሀዋሳ ኢንዱስትሪ ፓርክ',            'latitude' => 7.0573,  'longitude' => 38.4952, 'description' => 'Major industrial zone'],
            // Mekelle
            ['name_en' => 'Mekelle Market',             'name_am' => 'መቀለ ገበያ',                   'latitude' => 13.4967, 'longitude' => 39.4732, 'description' => 'Main market in Mekelle'],
            ['name_en' => 'Mekelle Airport',            'name_am' => 'መቀለ አውሮፕላን ማረፊያ',          'latitude' => 13.4737, 'longitude' => 39.5339, 'description' => 'Mekelle domestic airport'],
            // Gondar
            ['name_en' => 'Gondar Castle',              'name_am' => 'ጎንደር ፋሲለደስ ቤተ-መንግስት',      'latitude' => 12.6030, 'longitude' => 37.4682, 'description' => 'UNESCO World Heritage Site'],
            ['name_en' => 'Gondar Market',              'name_am' => 'ጎንደር ገበያ',                   'latitude' => 12.6064, 'longitude' => 37.4567, 'description' => 'Main market area'],
            // Adama
            ['name_en' => 'Adama Bus Station',          'name_am' => 'አዳማ አውቶቡስ ተርሚናል',           'latitude' => 8.5432,  'longitude' => 39.2725, 'description' => 'Main transport hub'],
            ['name_en' => 'Adama Market',               'name_am' => 'አዳማ ገበያ',                    'latitude' => 8.5398,  'longitude' => 39.2697, 'description' => 'Central market'],
        ];

        foreach ($landmarks as $landmark) {
            EthiopiaLandmark::firstOrCreate(
                ['name_en' => $landmark['name_en']],
                array_merge($landmark, ['uuid' => (string) Str::uuid()])
            );
        }
    }
}
