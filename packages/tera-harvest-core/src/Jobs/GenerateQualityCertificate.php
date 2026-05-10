<?php

namespace Fleetbase\TeraHarvest\Jobs;

use Fleetbase\TeraHarvest\Events\QualityCertificateReady;
use Fleetbase\TeraHarvest\Models\QualityGrade;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class GenerateQualityCertificate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly string $gradeId) {}

    public function handle(): void
    {
        $grade = QualityGrade::findOrFail($this->gradeId);

        $pdf  = app('dompdf.wrapper');
        $html = view('tera-harvest::certificates.quality_certificate', compact('grade'))->render();
        $pdf->loadHTML($html);
        $content = $pdf->output();

        $path = "certificates/QC-{$grade->certificate_number}.pdf";
        Storage::disk(config('tera_harvest.certificate.s3_disk'))->put($path, $content, 'public');
        $url  = Storage::disk(config('tera_harvest.certificate.s3_disk'))->url($path);
        $hash = hash('sha256', $content);

        $grade->update([
            'certificate_pdf_url' => $url,
            'certificate_hash'    => $hash,
            'status'              => 'issued',
        ]);

        QualityCertificateReady::dispatch($grade);
    }
}
