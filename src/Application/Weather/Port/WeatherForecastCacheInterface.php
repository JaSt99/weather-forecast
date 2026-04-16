<?php

declare(strict_types=1);

namespace App\Application\Weather\Port;

use App\Domain\Shared\ValueObject\Coordinates;
use App\Domain\Weather\Dto\WeatherForecast;

interface WeatherForecastCacheInterface
{
    public function get(Coordinates $coordinates): ?WeatherForecast;

    public function save(Coordinates $coordinates, WeatherForecast $forecast): void;

    public function delete(Coordinates $coordinates): void;
}
