<?php

namespace Fleetbase\TeraHarvest\Services\Payments;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChapaPaymentService
{
    private string $secretKey;
    private string $webhookSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey     = config('tera_harvest.chapa.secret_key');
        $this->webhookSecret = config('tera_harvest.chapa.webhook_secret');
        $this->baseUrl       = config('tera_harvest.chapa.base_url');
    }

    public function initiate(string $amount, string $email, string $phone, string $reference, string $callbackUrl): array
    {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'amount'       => $amount,
                'currency'     => 'ETB',
                'email'        => $email,
                'phone_number' => $phone,
                'tx_ref'       => $reference,
                'callback_url' => $callbackUrl,
            ]);

        $response->throw();
        return $response->json();
    }

    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        $response->throw();
        return $response->json();
    }

    public function validateWebhook(Request $request): bool
    {
        $signature = $request->header('Chapa-Signature');
        if (!$signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $request->getContent(), $this->webhookSecret);
        return hash_equals($expected, $signature);
    }
}
