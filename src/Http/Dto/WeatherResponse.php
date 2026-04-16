<?php

declare(strict_types=1);

namespace App\Http\Dto;

class WeatherResponse
{
    public string $city;

    /**
     * @var TemperatureEntry[]
     */
    public array $temperature;
}
