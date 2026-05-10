<?php

namespace Fleetbase\TeraHarvest\Console\Commands;

use Fleetbase\TeraHarvest\Database\Seeders\EthiopiaLandmarkSeeder;
use Fleetbase\TeraHarvest\Database\Seeders\EthiopiaRegionSeeder;
use Fleetbase\TeraHarvest\Database\Seeders\EthiopiaRouteSeeder;
use Illuminate\Console\Command;

class SeedEthiopiaData extends Command
{
    protected $signature   = 'tera-harvest:seed-ethiopia {--routes : Also seed major route corridors}';
    protected $description = 'Seed Ethiopian administrative hierarchy, landmarks, and (optionally) route segments';

    public function handle(): int
    {
        $this->info('Seeding Ethiopia regions and zones...');
        (new EthiopiaRegionSeeder())->run();
        $this->info('  ✓ Regions + zones seeded');

        $this->info('Seeding Ethiopia landmarks...');
        (new EthiopiaLandmarkSeeder())->run();
        $this->info('  ✓ Landmarks seeded');

        if ($this->option('routes')) {
            $this->info('Seeding major route corridors...');
            (new EthiopiaRouteSeeder())->run();
            $this->info('  ✓ Route segments seeded');
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
