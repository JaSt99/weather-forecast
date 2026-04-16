<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

enum CacheKey: string
{
    case WeatherForecast = 'weather_%s_%s';

    /** @phpstan-param string|int|float ...$args */
    public function key(mixed ...$args): string
    {
        return sprintf($this->value, ...$args);
    }
}
