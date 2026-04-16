<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Cache;

use App\Infrastructure\Cache\WeatherForecastCache;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Domain\Weather\Dto\WeatherForecast;
use App\Infrastructure\Cache\CacheKey;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class WeatherForecastCacheTest extends TestCase
{
    private CacheItemPoolInterface&MockObject $cachePool;
    private WeatherForecastCache $cache;

    protected function setUp(): void
    {
        $this->cachePool = $this->createMock(CacheItemPoolInterface::class);
        $this->cache = new WeatherForecastCache($this->cachePool);
    }

    #[Test]
    public function getReturnsForecastOnCacheHit(): void
    {
        $coordinates = $this->createCoordinates(50.0755, 14.4378);
        $forecast = new WeatherForecast(0.0, 0.0, '', []);

        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(true);
        $item->method('get')->willReturn($forecast);

        $expectedKey = CacheKey::WeatherForecast->key(50.0755, 14.4378);
        $this->cachePool->method('getItem')->with($expectedKey)->willReturn($item);

        $result = $this->cache->get($coordinates);

        $this->assertSame($forecast, $result);
    }

    #[Test]
    public function getReturnsNullOnCacheMiss(): void
    {
        $coordinates = $this->createCoordinates(50.0755, 14.4378);

        $item = $this->createMock(CacheItemInterface::class);
        $item->method('isHit')->willReturn(false);

        $this->cachePool->method('getItem')->willReturn($item);

        $result = $this->cache->get($coordinates);

        $this->assertNull($result);
    }

    #[Test]
    public function savePersistsForecastWithCorrectKeyAndExpiration(): void
    {
        $coordinates = $this->createCoordinates(49.1951, 16.6068);
        $forecast = new WeatherForecast(0.0, 0.0, '', []);

        $item = $this->createMock(CacheItemInterface::class);

        $expectedKey = CacheKey::WeatherForecast->key(49.1951, 16.6068);
        $this->cachePool->method('getItem')->with($expectedKey)->willReturn($item);

        $item->expects($this->once())->method('set')->with($forecast);
        $item->expects($this->once())->method('expiresAt')->with(
            $this->callback(fn (\DateTimeImmutable $dt) => $dt->format('Y-m-d') === (new \DateTimeImmutable('tomorrow midnight'))->format('Y-m-d'))
        );

        $this->cachePool->expects($this->once())->method('save')->with($item);

        $this->cache->save($coordinates, $forecast);
    }

    #[Test]
    public function deleteRemovesCacheItemByCoordinates(): void
    {
        $coordinates = $this->createCoordinates(50.0755, 14.4378);

        $expectedKey = CacheKey::WeatherForecast->key(50.0755, 14.4378);
        $this->cachePool->expects($this->once())
            ->method('deleteItem')
            ->with($expectedKey);

        $this->cache->delete($coordinates);
    }

    #[Test]
    public function cacheKeyIncludesCoordinates(): void
    {
        $lat = 50.0755;
        $lon = 14.4378;
        $expectedKey = sprintf('weather_%s_%s', $lat, $lon);

        $this->assertSame($expectedKey, CacheKey::WeatherForecast->key($lat, $lon));
    }

    private function createCoordinates(float $lat, float $lon): Coordinates
    {
        $coordinates = new Coordinates($lat, $lon);

        return $coordinates;
    }
}
