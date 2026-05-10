<?php

namespace Fleetbase\TeraHarvest\Http\Controllers;

use Fleetbase\TeraHarvest\Jobs\GenerateComplianceDocument;
use Fleetbase\TeraHarvest\Jobs\RunComplianceCheck;
use Fleetbase\TeraHarvest\Models\ComplianceChecklist;
use Fleetbase\TeraHarvest\Models\ComplianceDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use ZipStream\ZipStream;

class ComplianceController extends Controller
{
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id'      => 'required|string',
            'document_type' => 'required|in:phytosanitary,certificate_of_origin,ecx_grade,customs_declaration,bill_of_lading,packing_list,inspection_certificate',
        ]);

        $doc = ComplianceDocument::create([
            'uuid'            => (string) \Illuminate\Support\Str::uuid(),
            'order_id'        => $validated['order_id'],
            'document_type'   => $validated['document_type'],
            'document_number' => ComplianceDocument::generateDocumentNumber($validated['document_type']),
            'status'          => 'draft',
            'issued_by'       => auth()->id(),
        ]);

        GenerateComplianceDocument::dispatch($doc->id);

        return response()->json($doc, 202);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(ComplianceDocument::findOrFail($id));
    }

    public function download(string $id)
    {
        $doc = ComplianceDocument::findOrFail($id);
        if (!$doc->file_url) {
            return response()->json(['error' => 'Document not yet generated.'], 202);
        }
        return redirect($doc->file_url);
    }

    public function verify(string $id): JsonResponse
    {
        $doc = ComplianceDocument::findOrFail($id);

        return response()->json([
            'valid'        => $doc->status === 'issued',
            'hash_matches' => $doc->verifyHash(),
            'document_summary' => [
                'document_number' => $doc->document_number,
                'document_type'   => $doc->document_type,
                'order_id'        => $doc->order_id,
                'issued_at'       => $doc->issued_at,
                'expires_at'      => $doc->expires_at,
                'status'          => $doc->status,
            ],
        ]);
    }

    public function checklist(string $orderId): JsonResponse
    {
        $checklist = ComplianceChecklist::where('order_id', $orderId)->firstOrFail();
        return response()->json($checklist);
    }

    public function runCheck(string $orderId): JsonResponse
    {
        RunComplianceCheck::dispatch($orderId, auth()->id());
        return response()->json(['status' => 'check_queued', 'order_id' => $orderId]);
    }

    public function bundle(string $orderId)
    {
        $docs = ComplianceDocument::where('order_id', $orderId)
            ->whereNotNull('file_url')
            ->get();

        if ($docs->isEmpty()) {
            return response()->json(['error' => 'No documents for this order.'], 404);
        }

        $zipName = "compliance-{$orderId}.zip";

        return response()->streamDownload(function () use ($docs, $zipName) {
            $zip = new ZipStream(outputName: $zipName, sendHttpHeaders: false);
            foreach ($docs as $doc) {
                $contents = file_get_contents($doc->file_url);
                if ($contents !== false) {
                    $zip->addFile("{$doc->document_type}_{$doc->document_number}.pdf", $contents);
                }
            }
            $zip->finish();
        }, $zipName, ['Content-Type' => 'application/zip']);
    }
}
