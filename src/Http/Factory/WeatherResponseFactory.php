<?php

declare(strict_types=1);

namespace App\Http\Factory;

use App\Domain\Weather\Dto\DayForecast;
use App\Domain\Weather\Dto\WeatherForecast;
use App\Http\Dto\TemperatureEntry;
use App\Http\Dto\WeatherResponse;

final class WeatherResponseFactory
{
    public function create(string $cityName, WeatherForecast $forecast): WeatherResponse
    {
        $response = new WeatherResponse();
        $response->city = $cityName;
        $response->temperature = array_map($this->mapTemperatureEntry(...), $forecast->days);

        return $response;
    }

    private function mapTemperatureEntry(DayForecast $day): TemperatureEntry
    {
        $entry = new TemperatureEntry();
        $entry->date = $day->date;
        $entry->min = $day->temperatureMin;
        $entry->max = $day->temperatureMax;

        return $entry;
    }
}
