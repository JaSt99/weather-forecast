<?php

declare(strict_types=1);

namespace App\Application\Weather\Port;

use App\Domain\Shared\ValueObject\Coordinates;
use App\Domain\Weather\Dto\WeatherForecast;

interface WeatherForecastServiceInterface
{
    public function getForecastForCoordinates(Coordinates $coordinates): WeatherForecast;

    public function invalidateForecast(Coordinates $coordinates): void;
}
