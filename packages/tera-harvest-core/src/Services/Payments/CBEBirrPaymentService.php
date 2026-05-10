<?php

namespace Fleetbase\TeraHarvest\Services\Payments;

use Illuminate\Support\Facades\Http;

class CBEBirrPaymentService
{
    private string $merchantId;
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->merchantId = config('tera_harvest.cbe_birr.merchant_id');
        $this->apiKey     = config('tera_harvest.cbe_birr.api_key');
        $this->baseUrl    = config('tera_harvest.cbe_birr.base_url');
    }

    public function initiate(string $amount, string $accountNumber, string $reference): array
    {
        $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
            ->post("{$this->baseUrl}/payment/initialize", [
                'merchant_id'    => $this->merchantId,
                'amount'         => $amount,
                'currency'       => 'ETB',
                'account_number' => $accountNumber,
                'reference'      => $reference,
            ]);

        $response->throw();
        return $response->json();
    }

    public function verify(string $reference): array
    {
        $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
            ->get("{$this->baseUrl}/payment/verify/{$reference}");

        $response->throw();
        return $response->json();
    }
}
