<?php

declare(strict_types=1);

namespace App\Infrastructure\Client;

use App\Domain\Weather\Dto\DayForecast;
use App\Domain\Weather\Dto\WeatherForecast;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class WeatherForecastDenormalizer implements DenormalizerInterface
{
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): WeatherForecast
    {
        assert(is_array($data));

        return new WeatherForecast(
            $data['latitude'],
            $data['longitude'],
            $data['timezone'],
            $this->denormalizeDays($data['daily']),
        );
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return $type === WeatherForecast::class;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [WeatherForecast::class => true];
    }

    /**
     * @param array{time: array<int, string>, temperature_2m_min: array<int, float>, temperature_2m_max: array<int, float>} $daily
     * @return DayForecast[]
     */
    private function denormalizeDays(array $daily): array
    {
        $days = [];

        foreach ($daily['time'] as $index => $date) {
            $days[] = new DayForecast(
                $date,
                $daily['temperature_2m_min'][$index],
                $daily['temperature_2m_max'][$index],
            );
        }

        return $days;
    }
}
