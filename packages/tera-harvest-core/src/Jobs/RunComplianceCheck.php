<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\ComplianceChecklist;
use Fleetbase\TeraHarvest\Models\ComplianceDocument;
use Fleetbase\TeraHarvest\Services\AI\TeraHarvestAIClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class RunComplianceCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $orderId,
        public readonly ?string $requestedBy = null
    ) {}

    public function handle(TeraHarvestAIClient $ai): void
    {
        $docs = ComplianceDocument::where('order_id', $this->orderId)->get();

        $result = $ai->checkCompliance(
            $this->orderId,
            $docs->pluck('document_type')->all()
        );

        ComplianceChecklist::updateOrCreate(
            ['order_id' => $this->orderId],
            [
                'uuid'                => (string) Str::uuid(),
                'generated_by_agent'  => true,
                'items'               => $result['checklist'] ?? [],
                'overall_status'      => $result['is_compliant'] ? 'complete' : 'flagged',
                'flagged_items'       => $result['missing_documents'] ?? [],
            ]
        );
    }
}
