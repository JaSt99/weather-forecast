<?php

declare(strict_types=1);

namespace App\Domain\Weather\Dto;

readonly class WeatherForecast
{
    /**
     * @param DayForecast[] $days
     */
    public function __construct(
        public float $latitude,
        public float $longitude,
        public string $timezone,
        public array $days,
    ) {
    }
}
