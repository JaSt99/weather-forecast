<?php

declare(strict_types=1);

namespace App\Application\Weather\Port;

use App\Domain\Weather\Dto\WeatherForecast;

interface WeatherClientInterface
{
    public function getForecast(float $latitude, float $longitude): WeatherForecast;
}
