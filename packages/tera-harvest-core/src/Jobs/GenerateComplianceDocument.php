<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Models\ComplianceDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateComplianceDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $docId) {}

    public function handle(): void
    {
        $doc = ComplianceDocument::findOrFail($this->docId);

        $pdf  = app('dompdf.wrapper');
        $view = "tera-harvest::compliance.{$doc->document_type}";
        $html = view()->exists($view)
            ? view($view, compact('doc'))->render()
            : view('tera-harvest::compliance.generic', compact('doc'))->render();

        $pdf->loadHTML($html);
        $content = $pdf->output();

        $path = "compliance/{$doc->document_type}/{$doc->document_number}.pdf";
        Storage::disk(config('tera_harvest.compliance.s3_disk'))->put($path, $content, 'public');
        $url = Storage::disk(config('tera_harvest.compliance.s3_disk'))->url($path);

        // Chain hash: SHA-256 of previous doc in this order's chain
        $previousDoc = ComplianceDocument::where('order_id', $doc->order_id)
            ->whereNotNull('file_hash')
            ->latest('issued_at')
            ->first();

        $chainHash = hash('sha256', ($previousDoc?->file_hash ?? str_repeat('0', 64)) . hash('sha256', $content));

        $doc->update([
            'file_url'   => $url,
            'file_hash'  => hash('sha256', $content),
            'chain_hash' => $chainHash,
            'status'     => 'issued',
            'issued_at'  => now(),
        ]);
    }
}
