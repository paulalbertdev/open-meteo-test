<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\OpenMeteoClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenMeteoClientTest extends TestCase
{
    public function testGeocodeCityReturnsFirstResult(): void
    {
        $responses = [new MockResponse(json_encode([
            'results' => [
                [
                    'name' => 'Paris',
                    'admin1' => 'Ile-de-France',
                    'country' => 'France',
                    'latitude' => 48.8566,
                    'longitude' => 2.3522,
                ],
            ],
        ], JSON_THROW_ON_ERROR))];

        $client = new OpenMeteoClient(new MockHttpClient($responses));
        $result = $client->geocodeCity('Paris');

        $this->assertNotNull($result);
        $this->assertSame('Paris, Ile-de-France, France', $result->label);
        $this->assertSame(48.8566, $result->latitude);
        $this->assertSame(2.3522, $result->longitude);
    }

    public function testFetchForecastReturnsPayload(): void
    {
        $responses = [new MockResponse(json_encode([
            'current' => ['temperature_2m' => 12.5],
            'daily' => ['temperature_2m_max' => [15.0]],
            'timezone' => 'Europe/Paris',
        ], JSON_THROW_ON_ERROR))];

        $client = new OpenMeteoClient(new MockHttpClient($responses));
        $payload = $client->fetchForecast(48.8566, 2.3522);

        $this->assertSame('Europe/Paris', $payload['timezone']);
        $this->assertSame(12.5, $payload['current']['temperature_2m']);
    }
}
