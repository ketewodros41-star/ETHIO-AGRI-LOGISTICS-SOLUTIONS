<?php

namespace Fleetbase\TeraHarvest\Services;

use Fleetbase\TeraHarvest\Models\WeatherAlert;
use Fleetbase\TeraHarvest\Models\EthiopiaRegion;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherMonitoringService
{
    private const OPEN_METEO_URL = 'https://api.open-meteo.com/v1/forecast';

    // IPCC WMO thresholds
    private const THRESHOLDS = [
        'heavy_rain'   => ['precipitation_mm' => 50.0],
        'strong_wind'  => ['max_wind_speed_kmh' => 65.0],
        'extreme_heat' => ['max_temp_c' => 38.0],
        'frost'        => ['min_temp_c' => 2.0],
    ];

    public function checkRegion(EthiopiaRegion $region, string $companyId): array
    {
        $forecast = $this->fetchForecast($region->centroid_latitude ?? 9.03, $region->centroid_longitude ?? 38.74);

        if (!$forecast) {
            return [];
        }

        $alerts = [];
        foreach ($forecast['daily'] as $day) {
            $triggered = $this->evaluateThresholds($day);
            foreach ($triggered as $alertType => $params) {
                $severity = $this->determineSeverity($alertType, $params);
                $alert    = WeatherAlert::create([
                    'company_id'           => $companyId,
                    'alert_type'           => $alertType,
                    'severity'             => $severity,
                    'region_id'            => $region->id,
                    'max_wind_speed_kmh'   => $params['max_wind_speed_kmh'] ?? null,
                    'precipitation_mm'     => $params['precipitation_mm'] ?? null,
                    'min_temp_c'           => $params['min_temp_c'] ?? null,
                    'max_temp_c'           => $params['max_temp_c'] ?? null,
                    'raw_forecast'         => $day,
                    'forecast_valid_from'  => $day['date'] . ' 00:00:00',
                    'forecast_valid_until' => $day['date'] . ' 23:59:59',
                    'is_active'            => true,
                ]);
                $alerts[] = $alert;
            }
        }

        return $alerts;
    }

    private function fetchForecast(float $lat, float $lon): ?array
    {
        try {
            $response = Http::timeout(10)->get(self::OPEN_METEO_URL, [
                'latitude'       => $lat,
                'longitude'      => $lon,
                'daily'          => 'precipitation_sum,windspeed_10m_max,temperature_2m_max,temperature_2m_min',
                'timezone'       => 'Africa/Addis_Ababa',
                'forecast_days'  => 7,
            ]);

            if (!$response->successful()) {
                return null;
            }

            $data  = $response->json();
            $daily = [];
            foreach ($data['daily']['time'] as $i => $date) {
                $daily[] = [
                    'date'              => $date,
                    'precipitation_mm'  => $data['daily']['precipitation_sum'][$i] ?? 0,
                    'max_wind_speed_kmh' => $data['daily']['windspeed_10m_max'][$i] ?? 0,
                    'max_temp_c'        => $data['daily']['temperature_2m_max'][$i] ?? 0,
                    'min_temp_c'        => $data['daily']['temperature_2m_min'][$i] ?? 0,
                ];
            }
            return ['daily' => $daily];
        } catch (\Exception $e) {
            Log::warning('WeatherMonitoringService: forecast fetch failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function evaluateThresholds(array $day): array
    {
        $triggered = [];
        foreach (self::THRESHOLDS as $type => $criteria) {
            foreach ($criteria as $field => $threshold) {
                $value = $day[$field] ?? 0;
                if ($type === 'frost') {
                    if ($value <= $threshold) {
                        $triggered[$type][$field] = $value;
                    }
                } else {
                    if ($value >= $threshold) {
                        $triggered[$type][$field] = $value;
                    }
                }
            }
        }
        return $triggered;
    }

    private function determineSeverity(string $alertType, array $params): string
    {
        if ($alertType === 'heavy_rain') {
            $mm = $params['precipitation_mm'] ?? 0;
            if ($mm >= 150) return 'emergency';
            if ($mm >= 100) return 'warning';
        }
        if ($alertType === 'strong_wind') {
            $kmh = $params['max_wind_speed_kmh'] ?? 0;
            if ($kmh >= 100) return 'emergency';
            if ($kmh >= 80)  return 'warning';
        }
        return 'watch';
    }
}
