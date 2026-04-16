<?php

declare(strict_types=1);

namespace App\Infrastructure\Client;

use App\Application\Weather\Port\WeatherClientInterface;
use App\Domain\Weather\Dto\WeatherForecast;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\TransferException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

readonly class OpenMeteoClient implements WeatherClientInterface
{
    private const int FORECAST_DAYS = 7;

    private const array DAILY_VARIABLES = [
        'temperature_2m_min',
        'temperature_2m_max',
    ];

    public function __construct(
        private Client $client,
        private DenormalizerInterface $denormalizer,
        private string $baseUrl,
    ) {
    }

    public function getForecast(float $latitude, float $longitude): WeatherForecast
    {
        $data = $this->get($this->baseUrl, [
            'daily' => implode(',', self::DAILY_VARIABLES),
            'forecast_days' => self::FORECAST_DAYS,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timezone' => 'auto',
        ]);

        return $this->denormalizer->denormalize($data, WeatherForecast::class);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function get(string $url, array $query): array
    {
        try {
            $response = $this->client->request(Request::METHOD_GET, $url, ['query' => $query]);

            /** @var array<string, mixed> */
            return json_decode($response->getBody()->getContents(), true);
        } catch (ConnectException $e) {
            throw WeatherClientException::connectionFailed($e);
        } catch (TransferException $e) {
            throw WeatherClientException::unexpectedResponse($e);
        }
    }
}
