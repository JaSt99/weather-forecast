<?php

declare(strict_types=1);

namespace App\Tests\Unit\Http\Factory;

use App\Domain\Weather\Dto\DayForecast;
use App\Domain\Weather\Dto\WeatherForecast;
use App\Http\Dto\WeatherResponse;
use App\Http\Factory\WeatherResponseFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WeatherResponseFactoryTest extends TestCase
{
    private WeatherResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new WeatherResponseFactory();
    }

    #[Test]
    public function createSetsCityName(): void
    {
        $result = $this->factory->create('Praha', $this->buildForecast([]));

        $this->assertSame('Praha', $result->city);
    }

    #[Test]
    public function createMapsAllDaysToTemperatureEntries(): void
    {
        $forecast = $this->buildForecast([
            new DayForecast('2026-04-16', 8.5, 17.2),
            new DayForecast('2026-04-17', 10.0, 20.5),
            new DayForecast('2026-04-18', 7.3, 15.0),
        ]);

        $result = $this->factory->create('Praha', $forecast);

        $this->assertInstanceOf(WeatherResponse::class, $result);
        $this->assertCount(3, $result->temperature);
    }

    #[Test]
    public function createMapsTemperatureEntryFields(): void
    {
        $forecast = $this->buildForecast([
            new DayForecast('2026-04-16', 8.5, 17.2),
        ]);

        $result = $this->factory->create('Praha', $forecast);

        $entry = $result->temperature[0];
        $this->assertSame('2026-04-16', $entry->date);
        $this->assertSame(8.5, $entry->min);
        $this->assertSame(17.2, $entry->max);
    }

    #[Test]
    public function createReturnsEmptyTemperatureArrayWhenNoDays(): void
    {
        $result = $this->factory->create('Praha', $this->buildForecast([]));

        $this->assertSame([], $result->temperature);
    }

    private function buildForecast(array $days): WeatherForecast
    {
        return new WeatherForecast(50.0755, 14.4378, 'Europe/Prague', $days);
    }
}
