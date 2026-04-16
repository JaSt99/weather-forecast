<?php

declare(strict_types=1);

namespace App\Infrastructure\Cache;

use App\Application\Weather\Port\WeatherForecastCacheInterface;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Domain\Weather\Dto\WeatherForecast;
use Psr\Cache\CacheItemPoolInterface;

readonly class WeatherForecastCache implements WeatherForecastCacheInterface
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function get(Coordinates $coordinates): ?WeatherForecast
    {
        $item = $this->cache->getItem($this->buildKey($coordinates));

        $value = $item->get();

        return $value instanceof WeatherForecast ? $value : null;
    }

    public function save(Coordinates $coordinates, WeatherForecast $forecast): void
    {
        $item = $this->cache->getItem($this->buildKey($coordinates));
        $item->set($forecast);
        $item->expiresAt(new \DateTimeImmutable('tomorrow midnight'));
        $this->cache->save($item);
    }

    public function delete(Coordinates $coordinates): void
    {
        $this->cache->deleteItem($this->buildKey($coordinates));
    }

    private function buildKey(Coordinates $coordinates): string
    {
        return CacheKey::WeatherForecast->key($coordinates->latitude, $coordinates->longitude);
    }
}
