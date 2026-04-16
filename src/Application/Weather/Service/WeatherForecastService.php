<?php

declare(strict_types=1);

namespace App\Application\Weather\Service;

use App\Application\Weather\Port\WeatherClientInterface;
use App\Application\Weather\Port\WeatherForecastCacheInterface;
use App\Application\Weather\Port\WeatherForecastServiceInterface;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Domain\Weather\Dto\WeatherForecast;

readonly class WeatherForecastService implements WeatherForecastServiceInterface
{
    public function __construct(
        private WeatherClientInterface $weatherClient,
        private WeatherForecastCacheInterface $forecastCache,
    ) {
    }

    public function getForecastForCoordinates(Coordinates $coordinates): WeatherForecast
    {
        return $this->getForecast($coordinates);
    }

    public function invalidateForecast(Coordinates $coordinates): void
    {
        $this->forecastCache->delete($coordinates);
    }

    private function getForecast(Coordinates $coordinates): WeatherForecast
    {
        $forecast = $this->forecastCache->get($coordinates);

        if ($forecast !== null) {
            return $forecast;
        }

        $forecast = $this->weatherClient->getForecast($coordinates->latitude, $coordinates->longitude);
        $this->forecastCache->save($coordinates, $forecast);

        return $forecast;
    }
}
