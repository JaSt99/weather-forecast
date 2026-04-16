<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\City\Service;

use App\Application\City\Dto\CityData;
use App\Application\City\Service\CityService;
use App\Domain\City\Entity\City;
use App\Domain\City\Exception\CityNotFoundException;
use App\Domain\City\Exception\DuplicateCityCoordinatesException;
use App\Domain\City\Repository\CityRepositoryInterface;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Application\Weather\Port\WeatherForecastServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CityServiceTest extends TestCase
{
    private CityRepositoryInterface&MockObject $repository;
    private WeatherForecastServiceInterface&MockObject $weatherService;
    private CityService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CityRepositoryInterface::class);
        $this->weatherService = $this->createMock(WeatherForecastServiceInterface::class);
        $this->service = new CityService($this->repository, $this->weatherService);
    }

    #[Test]
    public function getReturnsCity(): void
    {
        $city = $this->createCity('Praha', 50.0755, 14.4378);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($city);

        $result = $this->service->get(1);

        $this->assertSame($city, $result);
    }

    #[Test]
    public function getThrowsCityNotFoundExceptionWhenCityDoesNotExist(): void
    {
        $this->repository->method('find')->willReturn(null);

        $this->expectException(CityNotFoundException::class);
        $this->expectExceptionMessage('City with ID 99 not found.');

        $this->service->get(99);
    }

    #[Test]
    public function getByNameReturnsCity(): void
    {
        $city = $this->createCity('Praha', 50.0755, 14.4378);

        $this->repository->expects($this->once())
            ->method('findAllByName')
            ->with('Praha')
            ->willReturn([$city]);

        $result = $this->service->getByName('Praha');

        $this->assertSame($city, $result);
    }

    #[Test]
    public function getByNameThrowsCityNotFoundExceptionWhenCityDoesNotExist(): void
    {
        $this->repository->method('findAllByName')->willReturn([]);

        $this->expectException(CityNotFoundException::class);
        $this->expectExceptionMessage('City "Neznámé" not found.');

        $this->service->getByName('Neznámé');
    }

    #[Test]
    public function getByNameThrowsAmbiguousCityNameExceptionWhenMultipleCitiesFound(): void
    {
        $cities = [
            $this->createCity('Springfield', 39.7817, -89.6501),
            $this->createCity('Springfield', 37.2153, -93.2982),
        ];
        $this->repository->method('findAllByName')->willReturn($cities);

        $this->expectException(\App\Domain\City\Exception\AmbiguousCityNameException::class);

        $this->service->getByName('Springfield');
    }

    #[Test]
    public function findAllReturnsCitiesFromRepository(): void
    {
        $cities = [
            $this->createCity('Praha', 50.0755, 14.4378),
            $this->createCity('Brno', 49.1951, 16.6068),
        ];

        $this->repository->method('findAll')->willReturn($cities);

        $result = $this->service->findAll();

        $this->assertSame($cities, $result);
    }

    #[Test]
    public function createPersistsCityAndReturnsIt(): void
    {
        $data = new CityData('Ostrava', new Coordinates(49.8209, 18.2625));

        $this->repository->method('findByCoordinates')->willReturn(null);
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(City::class));

        $result = $this->service->create($data);

        $this->assertInstanceOf(City::class, $result);
        $this->assertSame('Ostrava', $result->name);
        $this->assertSame(49.8209, $result->latitude);
        $this->assertSame(18.2625, $result->longitude);
    }

    #[Test]
    public function createThrowsDuplicateCityCoordinatesExceptionWhenCoordinatesTaken(): void
    {
        $data = new CityData('Ostrava', new Coordinates(49.8209, 18.2625));
        $existing = $this->createCity('Jiné město', 49.8209, 18.2625);

        $this->repository->method('findByCoordinates')->willReturn($existing);

        $this->expectException(DuplicateCityCoordinatesException::class);

        $this->service->create($data);
    }

    #[Test]
    public function updateEditsAndSavesCity(): void
    {
        $city = $this->createCity('Praha', 50.0755, 14.4378);
        $data = new CityData('Praha-update', new Coordinates(50.0755, 14.4378));

        $this->repository->method('find')->willReturn($city);
        $this->repository->method('findByCoordinates')->willReturn(null);
        $this->repository->expects($this->once())->method('save')->with($city);

        $result = $this->service->update(1, $data);

        $this->assertSame($city, $result);
        $this->assertSame('Praha-update', $result->name);
    }

    #[Test]
    public function updateThrowsDuplicateCityCoordinatesExceptionWhenCoordinatesTakenByAnotherCity(): void
    {
        $city = $this->createCity('Praha', 50.0755, 14.4378);
        $other = $this->createCity('Brno', 49.1951, 16.6068);
        $data = new CityData('Praha', new Coordinates(49.1951, 16.6068));

        $this->repository->method('find')->willReturn($city);
        $this->repository->method('findByCoordinates')->willReturn($other);

        $this->expectException(DuplicateCityCoordinatesException::class);

        $this->service->update(1, $data);
    }

    #[Test]
    public function updateAllowsSavingWithSameCoordinatesForSameCity(): void
    {
        $city = $this->createCity('Praha', 50.0755, 14.4378);
        $data = new CityData('Praha-renamed', new Coordinates(50.0755, 14.4378));

        $this->repository->method('find')->willReturn($city);
        $this->repository->method('findByCoordinates')->willReturn($city);
        $this->repository->expects($this->once())->method('save');

        $result = $this->service->update(1, $data);

        $this->assertSame('Praha-renamed', $result->name);
    }

    #[Test]
    public function updateInvalidatesForecastCacheWhenCoordinatesChange(): void
    {
        $city = $this->createCity('Praha', 50.0755, 14.4378);
        $data = new CityData('Praha', new Coordinates(51.0, 15.0));

        $this->repository->method('find')->willReturn($city);
        $this->repository->method('findByCoordinates')->willReturn(null);

        $this->weatherService->expects($this->once())
            ->method('invalidateForecast')
            ->with($this->callback(fn ($c) => $c->latitude === 50.0755 && $c->longitude === 14.4378));

        $this->service->update(1, $data);
    }

    #[Test]
    public function updateDoesNotInvalidateForecastCacheWhenOnlyNameChanges(): void
    {
        $city = $this->createCity('Praha', 50.0755, 14.4378);
        $data = new CityData('Praha-renamed', new Coordinates(50.0755, 14.4378));

        $this->repository->method('find')->willReturn($city);
        $this->repository->method('findByCoordinates')->willReturn($city);

        $this->weatherService->expects($this->never())->method('invalidateForecast');

        $this->service->update(1, $data);
    }

    #[Test]
    public function updateThrowsCityNotFoundExceptionWhenCityDoesNotExist(): void
    {
        $this->repository->method('find')->willReturn(null);

        $this->expectException(CityNotFoundException::class);

        $this->service->update(99, new CityData('x', new Coordinates(0.0, 0.0)));
    }

    #[Test]
    public function removeDelegatesToRepository(): void
    {
        $city = $this->createCity('Praha', 50.0755, 14.4378);

        $this->repository->method('find')->willReturn($city);
        $this->repository->expects($this->once())
            ->method('remove')
            ->with($city);

        $this->service->remove(1);
    }

    #[Test]
    public function removeThrowsCityNotFoundExceptionWhenCityDoesNotExist(): void
    {
        $this->repository->method('find')->willReturn(null);

        $this->expectException(CityNotFoundException::class);

        $this->service->remove(99);
    }

    private function createCity(string $name, float $lat, float $lon): City
    {
        return new City($name, new Coordinates($lat, $lon));
    }
}
