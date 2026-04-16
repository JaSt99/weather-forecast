<?php

declare(strict_types=1);

namespace App\Domain\Weather\Dto;

readonly class DayForecast
{
    public function __construct(
        public string $date,
        public float $temperatureMin,
        public float $temperatureMax,
    ) {
    }
}
