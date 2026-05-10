<?php

namespace Fleetbase\TeraHarvest\Services\AI;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TeraHarvestAIClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout = 30
    ) {}

    public function suggestPrice(string $cropType, float $quantityKg, string $woredaId): array
    {
        return $this->post('/agents/price-intelligence', [
            'crop_type'   => $cropType,
            'quantity_kg' => $quantityKg,
            'woreda_id'   => $woredaId,
        ]);
    }

    public function resolveAddress(string $description, ?float $lat = null, ?float $lng = null, string $language = 'am'): array
    {
        return $this->post('/agents/address-resolver', [
            'description' => $description,
            'lat'         => $lat,
            'lng'         => $lng,
            'language'    => $language,
        ]);
    }

    public function dispatchRecommendation(string $orderId, array $availableDrivers = [], array $roadConditions = []): array
    {
        return $this->post('/agents/smart-dispatch', [
            'order_id'          => $orderId,
            'available_drivers' => $availableDrivers,
            'road_conditions'   => $roadConditions,
        ]);
    }

    public function forecastDemand(string $cropType, string $regionId, int $daysAhead, array $historicalData = []): array
    {
        return $this->post('/agents/demand-forecast', [
            'crop_type'       => $cropType,
            'region_id'       => $regionId,
            'days_ahead'      => $daysAhead,
            'historical_data' => $historicalData,
        ]);
    }

    public function checkCompliance(string $orderId, array $documents = [], string $cropType = '', ?string $destinationCountry = null): array
    {
        return $this->post('/agents/compliance-check', [
            'order_id'            => $orderId,
            'documents'           => $documents,
            'crop_type'           => $cropType,
            'destination_country' => $destinationCountry,
        ]);
    }

    public function predictShelfLife(array $params): array
    {
        return $this->post('/agents/shelf-life', $params);
    }

    public function draftSmsMessage(string $eventType, array $context, string $language = 'am'): array
    {
        return $this->post('/agents/sms-draft', [
            'event_type' => $eventType,
            'context'    => $context,
            'language'   => $language,
        ]);
    }

    public function generateInsightReport(string $tenantId, string $period, array $dataSummary = []): array
    {
        return $this->post('/agents/insight-report', [
            'tenant_id'    => $tenantId,
            'period'       => $period,
            'data_summary' => $dataSummary,
        ]);
    }

    public function explainCreditScore(array $params): array
    {
        return $this->post('/agents/credit-score-explain', $params);
    }

    public function diagnoseCropDisease(array $params): array
    {
        return $this->post('/agents/disease-diagnosis', $params);
    }

    public function generateWeatherAlert(array $params): array
    {
        return $this->post('/agents/weather-alert', $params);
    }

    public function suggestNegotiationPrice(array $params): array
    {
        return $this->post('/agents/price-negotiation', $params);
    }

    public function predictYield(array $params): array
    {
        return $this->post('/agents/yield-prediction', $params);
    }

    private function post(string $endpoint, array $data): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->baseUrl($this->baseUrl)
                ->post($endpoint, $data);

            $response->throw();

            return $response->json();
        } catch (RequestException $e) {
            Log::error('TeraHarvestAIClient request failed', [
                'endpoint' => $endpoint,
                'status'   => $e->response?->status(),
                'body'     => $e->response?->body(),
            ]);
            throw $e;
        }
    }
}
