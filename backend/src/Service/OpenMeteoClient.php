<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class OpenMeteoClient
{
    public function __construct(private readonly HttpClientInterface $client) {}

    public function geocodeCity(string $city): ?GeoResult
    {
        $payload = $this->requestJson('https://geocoding-api.open-meteo.com/v1/search', [
            'name' => $city,
            'count' => 1,
            'language' => 'fr',
            'format' => 'json',
        ]);

        if (!isset($payload['results'][0])) {
            return null;
        }

        $result = $payload['results'][0];
        if (!isset($result['latitude'], $result['longitude'], $result['name'])) {
            return null;
        }

        $labelParts = array_filter([
            $result['name'],
            $result['admin1'] ?? null,
            $result['country'] ?? null,
        ], static fn($value) => is_string($value) && $value !== '');

        $label = implode(', ', $labelParts);

        return new GeoResult($label, (float) $result['latitude'], (float) $result['longitude']);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchForecast(float $latitude, float $longitude): array
    {
        return $this->requestJson('https://api.open-meteo.com/v1/forecast', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'current' => 'temperature_2m,wind_speed_10m',
            'daily' => 'temperature_2m_max,temperature_2m_min,wind_speed_10m_max,weather_code',
            'timezone' => 'auto',
        ]);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function requestJson(string $url, array $query): array
    {
        try {
            $response = $this->client->request('GET', $url, ['query' => $query]);
            return $response->toArray(false);
        } catch (ExceptionInterface $exception) {
            throw new \RuntimeException('Open-Meteo request failed.', 0, $exception);
        }
    }
}
