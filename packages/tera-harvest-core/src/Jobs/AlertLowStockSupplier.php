<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\InputProduct;
use Fleetbase\TeraHarvest\Services\Notifications\AfricasTalkingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AlertLowStockSupplier implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(private string $companyId) {}

    public function handle(AfricasTalkingService $sms): void
    {
        $products = InputProduct::where('company_id', $this->companyId)
                                ->active()
                                ->get()
                                ->filter(fn($p) => $p->isLowStock());

        foreach ($products as $product) {
            $phone = DB::table('input_suppliers')
                       ->where('id', $product->supplier_id)
                       ->value('contact_phone');

            if ($phone) {
                $sms->send($phone, "Low stock alert: {$product->name} has only {$product->stock_quantity} {$product->unit} remaining (threshold: {$product->reorder_threshold}).");
            }
        }
    }
}
