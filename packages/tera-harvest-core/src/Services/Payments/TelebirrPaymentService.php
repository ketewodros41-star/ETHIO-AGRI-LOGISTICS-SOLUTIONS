<?php

namespace Fleetbase\TeraHarvest\Services\Payments;

use Illuminate\Support\Facades\Http;

class TelebirrPaymentService
{
    private string $appId;
    private string $appKey;
    private string $shortCode;
    private string $baseUrl;

    public function __construct()
    {
        $this->appId     = config('tera_harvest.telebirr.app_id');
        $this->appKey    = config('tera_harvest.telebirr.app_key');
        $this->shortCode = config('tera_harvest.telebirr.short_code');
        $this->baseUrl   = config('tera_harvest.telebirr.base_url');
    }

    public function initiate(string $amount, string $phone, string $reference): array
    {
        $timestamp = now()->timestamp;
        $nonce     = bin2hex(random_bytes(8));
        $sign      = hash('sha256', $this->appKey . $timestamp . $nonce . $amount . $reference);

        $response = Http::post("{$this->baseUrl}/payment/initialize", [
            'appId'     => $this->appId,
            'shortCode' => $this->shortCode,
            'amount'    => $amount,
            'phone'     => $phone,
            'outTradeNo'=> $reference,
            'timestamp' => $timestamp,
            'nonce'     => $nonce,
            'sign'      => $sign,
        ]);

        $response->throw();
        return $response->json();
    }

    public function verify(string $reference): array
    {
        $response = Http::post("{$this->baseUrl}/payment/query", [
            'appId'      => $this->appId,
            'outTradeNo' => $reference,
        ]);

        $response->throw();
        return $response->json();
    }

    public function bulkDisbursement(array $payments): array
    {
        $response = Http::post("{$this->baseUrl}/payment/bulk-disburse", [
            'appId'    => $this->appId,
            'payments' => $payments,
        ]);

        $response->throw();
        return $response->json();
    }
}
