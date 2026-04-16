<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Client;

use App\Domain\Weather\Dto\WeatherForecast;
use App\Infrastructure\Client\WeatherForecastDenormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WeatherForecastDenormalizerTest extends TestCase
{
    private WeatherForecastDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new WeatherForecastDenormalizer();
    }

    #[Test]
    public function denormalizeMapsTopLevelFieldsCorrectly(): void
    {
        $data = $this->buildApiData();

        /** @var WeatherForecast $result */
        $result = $this->denormalizer->denormalize($data, WeatherForecast::class);

        $this->assertInstanceOf(WeatherForecast::class, $result);
        $this->assertSame(50.0755, $result->latitude);
        $this->assertSame(14.4378, $result->longitude);
        $this->assertSame('Europe/Prague', $result->timezone);
    }

    #[Test]
    public function denormalizeMapsAllDaysCorrectly(): void
    {
        $data = $this->buildApiData();

        /** @var WeatherForecast $result */
        $result = $this->denormalizer->denormalize($data, WeatherForecast::class);

        $this->assertCount(3, $result->days);
    }

    #[Test]
    public function denormalizeMapsIndividualDayFields(): void
    {
        $data = $this->buildApiData();

        /** @var WeatherForecast $result */
        $result = $this->denormalizer->denormalize($data, WeatherForecast::class);

        $firstDay = $result->days[0];
        $this->assertSame('2026-04-16', $firstDay->date);
        $this->assertSame(8.5, $firstDay->temperatureMin);
        $this->assertSame(17.2, $firstDay->temperatureMax);
    }

    #[Test]
    public function denormalizeMapsCorrectlyByIndex(): void
    {
        $data = $this->buildApiData();

        /** @var WeatherForecast $result */
        $result = $this->denormalizer->denormalize($data, WeatherForecast::class);

        $secondDay = $result->days[1];
        $this->assertSame('2026-04-17', $secondDay->date);
        $this->assertSame(10.0, $secondDay->temperatureMin);
        $this->assertSame(20.5, $secondDay->temperatureMax);
    }

    #[Test]
    public function supportsDenormalizationReturnsTrueForWeatherForecast(): void
    {
        $this->assertTrue(
            $this->denormalizer->supportsDenormalization([], WeatherForecast::class)
        );
    }

    #[Test]
    public function supportsDenormalizationReturnsFalseForOtherTypes(): void
    {
        $this->assertFalse(
            $this->denormalizer->supportsDenormalization([], \stdClass::class)
        );
        $this->assertFalse(
            $this->denormalizer->supportsDenormalization([], 'SomeOtherClass')
        );
    }

    #[Test]
    public function getSupportedTypesReturnsWeatherForecastWithCacheable(): void
    {
        $supported = $this->denormalizer->getSupportedTypes(null);

        $this->assertArrayHasKey(WeatherForecast::class, $supported);
        $this->assertTrue($supported[WeatherForecast::class]);
    }

    private function buildApiData(): array
    {
        return [
            'latitude' => 50.0755,
            'longitude' => 14.4378,
            'timezone' => 'Europe/Prague',
            'daily' => [
                'time' => ['2026-04-16', '2026-04-17', '2026-04-18'],
                'temperature_2m_min' => [8.5, 10.0, 7.3],
                'temperature_2m_max' => [17.2, 20.5, 15.0],
                'precipitation_sum' => [0.0, 2.3, 5.1],
                'weather_code' => [1, 63, 80],
            ],
        ];
    }
}
