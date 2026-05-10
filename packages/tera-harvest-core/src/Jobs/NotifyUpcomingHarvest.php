<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\YieldPrediction;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class NotifyUpcomingHarvest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(AfricasTalkingService $sms): void
    {
        $predictions = YieldPrediction::whereNotNull('harvest_expected_start')
                                       ->whereNull('harvest_actual_date')
                                       ->whereDate('harvest_expected_start', '=', now()->addDays(14)->toDateString())
                                       ->get();

        foreach ($predictions as $prediction) {
            $phone = DB::table('users')->where('id', $prediction->farmer_id)->value('phone');
            if (!$phone) {
                continue;
            }

            $kg      = number_format((float) $prediction->predicted_yield_kg, 0);
            $date    = $prediction->harvest_expected_start->format('d/m/Y');
            $crop    = $prediction->crop_type;
            $sms->send($phone, "ምርት ማጨድ ማስታወሻ: {$crop} ምርት (ወደ {$kg} ኪሎ) ቀን {$date} ይጠናቀቃሉ። ዝግጅቱን ያቅርቡ!");
        }
    }
}
