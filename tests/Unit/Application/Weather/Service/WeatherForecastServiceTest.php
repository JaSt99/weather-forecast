<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Weather\Service;

use App\Application\Weather\Port\WeatherForecastCacheInterface;
use App\Application\Weather\Service\WeatherForecastService;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Domain\Weather\Dto\WeatherForecast;
use App\Application\Weather\Port\WeatherClientInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WeatherForecastServiceTest extends TestCase
{
    private WeatherClientInterface&MockObject $client;
    private WeatherForecastCacheInterface&MockObject $cache;
    private WeatherForecastService $service;

    protected function setUp(): void
    {
        $this->client = $this->createMock(WeatherClientInterface::class);
        $this->cache = $this->createMock(WeatherForecastCacheInterface::class);
        $this->service = new WeatherForecastService($this->client, $this->cache);
    }

    #[Test]
    public function getForecastForCoordinatesReturnsCachedForecastWithoutCallingClient(): void
    {
        $coordinates = new Coordinates(50.0755, 14.4378);
        $cached = new WeatherForecast(50.0755, 14.4378, 'Europe/Prague', []);

        $this->cache->method('get')->with($coordinates)->willReturn($cached);
        $this->client->expects($this->never())->method('getForecast');

        $result = $this->service->getForecastForCoordinates($coordinates);

        $this->assertSame($cached, $result);
    }

    #[Test]
    public function getForecastForCoordinatesFetchesFromClientWhenNotCached(): void
    {
        $coordinates = new Coordinates(50.0755, 14.4378);
        $fresh = new WeatherForecast(50.0755, 14.4378, 'Europe/Prague', []);

        $this->cache->method('get')->willReturn(null);
        $this->client->expects($this->once())
            ->method('getForecast')
            ->with(50.0755, 14.4378)
            ->willReturn($fresh);

        $result = $this->service->getForecastForCoordinates($coordinates);

        $this->assertSame($fresh, $result);
    }

    #[Test]
    public function getForecastForCoordinatesSavesFetchedForecastToCache(): void
    {
        $coordinates = new Coordinates(50.0755, 14.4378);
        $fresh = new WeatherForecast(50.0755, 14.4378, 'Europe/Prague', []);

        $this->cache->method('get')->willReturn(null);
        $this->client->method('getForecast')->willReturn($fresh);

        $this->cache->expects($this->once())
            ->method('save')
            ->with($coordinates, $fresh);

        $this->service->getForecastForCoordinates($coordinates);
    }

    #[Test]
    public function getForecastForCoordinatesDoesNotSaveToCacheWhenAlreadyCached(): void
    {
        $coordinates = new Coordinates(50.0755, 14.4378);

        $this->cache->method('get')->willReturn(new WeatherForecast(0.0, 0.0, '', []));
        $this->cache->expects($this->never())->method('save');

        $this->service->getForecastForCoordinates($coordinates);
    }

    #[Test]
    public function invalidateForecastDeletesFromCache(): void
    {
        $coordinates = new Coordinates(50.0755, 14.4378);

        $this->cache->expects($this->once())->method('delete')->with($coordinates);

        $this->service->invalidateForecast($coordinates);
    }
}
