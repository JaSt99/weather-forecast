<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Client;

use App\Infrastructure\Client\OpenMeteoClient;
use App\Domain\Weather\Dto\WeatherForecast;
use App\Infrastructure\Client\WeatherClientException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class OpenMeteoClientTest extends TestCase
{
    private Client&MockObject $guzzle;
    private DenormalizerInterface&MockObject $denormalizer;
    private OpenMeteoClient $client;

    protected function setUp(): void
    {
        $this->guzzle = $this->createMock(Client::class);
        $this->denormalizer = $this->createMock(DenormalizerInterface::class);
        $this->client = new OpenMeteoClient($this->guzzle, $this->denormalizer, 'https://api.open-meteo.com/v1/forecast');
    }

    #[Test]
    public function getForecastReturnsWeatherForecast(): void
    {
        $apiPayload = $this->buildApiPayload();
        $forecast = new WeatherForecast(0.0, 0.0, '', []);

        $response = new Response(200, [], json_encode($apiPayload));

        $this->guzzle->method('request')
            ->with('GET', 'https://api.open-meteo.com/v1/forecast', $this->callback(
                fn (array $opts) => isset($opts['query']['latitude'])
                    && $opts['query']['latitude'] === 50.0755
                    && $opts['query']['longitude'] === 14.4378
                    && $opts['query']['forecast_days'] === 7
            ))
            ->willReturn($response);

        $this->denormalizer->method('denormalize')
            ->with($apiPayload, WeatherForecast::class)
            ->willReturn($forecast);

        $result = $this->client->getForecast(50.0755, 14.4378);

        $this->assertSame($forecast, $result);
    }

    #[Test]
    public function getForecastThrowsWeatherClientExceptionOnConnectionFailure(): void
    {
        $this->guzzle->method('request')
            ->willThrowException(new ConnectException('Connection refused', new Request('GET', '/')));

        $this->expectException(WeatherClientException::class);
        $this->expectExceptionMessage('Weather service is unavailable.');

        $this->client->getForecast(50.0755, 14.4378);
    }

    #[Test]
    public function getForecastThrowsWeatherClientExceptionOnUnexpectedResponse(): void
    {
        $this->guzzle->method('request')
            ->willThrowException(new ServerException(
                'Server error',
                new Request('GET', '/'),
                new Response(500)
            ));

        $this->expectException(WeatherClientException::class);
        $this->expectExceptionMessage('Weather service returned an unexpected response.');

        $this->client->getForecast(50.0755, 14.4378);
    }

    #[Test]
    public function getForecastRequestContainsAllRequiredDailyVariables(): void
    {
        $response = new Response(200, [], json_encode($this->buildApiPayload()));
        $forecast = new WeatherForecast(0.0, 0.0, '', []);

        $this->guzzle->expects($this->once())
            ->method('request')
            ->with('GET', $this->anything(), $this->callback(function (array $opts): bool {
                $daily = $opts['query']['daily'];
                $this->assertStringContainsString('temperature_2m_min', $daily);
                $this->assertStringContainsString('temperature_2m_max', $daily);

                return true;
            }))
            ->willReturn($response);

        $this->denormalizer->method('denormalize')->willReturn($forecast);

        $this->client->getForecast(50.0755, 14.4378);
    }

    private function buildApiPayload(): array
    {
        return [
            'latitude' => 50.0755,
            'longitude' => 14.4378,
            'timezone' => 'Europe/Prague',
            'daily' => [
                'time' => ['2026-04-16'],
                'temperature_2m_min' => [8.5],
                'temperature_2m_max' => [17.2],
                'precipitation_sum' => [0.0],
                'weather_code' => [1],
            ],
        ];
    }
}
