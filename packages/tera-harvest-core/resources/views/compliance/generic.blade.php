<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <title>{{ strtoupper(str_replace('_', ' ', $doc->document_type)) }} — {{ $doc->document_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        .header { text-align: center; border-bottom: 3px solid #1565C0; padding-bottom: 16px; margin-bottom: 24px; }
        .header h1 { font-size: 20px; color: #1565C0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table td { padding: 6px 8px; border: 1px solid #ddd; }
        table td:first-child { font-weight: bold; background: #E3F2FD; width: 40%; }
        .hash { font-family: monospace; font-size: 9px; word-break: break-all; }
        .footer { margin-top: 40px; font-size: 10px; color: #777; border-top: 1px solid #aaa; padding-top: 8px; }
    </style>
</head>
<body>
<div class="header">
    <h1>{{ strtoupper(str_replace('_', ' ', $doc->document_type)) }}</h1>
    <p>Tera Harvest — Ethiopian Agricultural Supply Chain</p>
</div>

<table>
    <tr><td>Document Number</td><td>{{ $doc->document_number }}</td></tr>
    <tr><td>Order ID</td><td>{{ $doc->order_id }}</td></tr>
    <tr><td>Issued By</td><td>{{ $doc->issued_by }}</td></tr>
    <tr><td>Issued At</td><td>{{ $doc->issued_at?->format('d M Y') }}</td></tr>
    <tr><td>Expires At</td><td>{{ $doc->expires_at?->format('d M Y') ?? 'N/A' }}</td></tr>
    <tr><td>Authority</td><td>{{ $doc->authority ?? 'Tera Harvest' }}</td></tr>
    <tr><td>Authority Reference</td><td>{{ $doc->authority_reference ?? '—' }}</td></tr>
</table>

<p><strong>Tamper-Detection Hash:</strong></p>
<p class="hash">{{ $doc->file_hash ?? 'Computed on generation' }}</p>
<p>Verify at: /api/v1/compliance/documents/{{ $doc->id }}/verify</p>

<div class="footer">Generated {{ now()->format('d M Y H:i:s') }} EAT — Tera Harvest</div>
</body>
</html>
