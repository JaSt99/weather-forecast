<?php

declare(strict_types=1);

namespace App\Application\City\Service;

use App\Application\City\Dto\CityData;
use App\Application\City\Port\CityServiceInterface;
use App\Application\Weather\Port\WeatherForecastServiceInterface;
use App\Domain\City\Entity\City;
use App\Domain\City\Exception\AmbiguousCityNameException;
use App\Domain\City\Exception\CityNotFoundException;
use App\Domain\City\Exception\DuplicateCityCoordinatesException;
use App\Domain\City\Repository\CityRepositoryInterface;

readonly class CityService implements CityServiceInterface
{
    public function __construct(
        private CityRepositoryInterface $cityRepository,
        private WeatherForecastServiceInterface $weatherService,
    ) {
    }

    public function get(int $id): City
    {
        $city = $this->cityRepository->find($id);

        if ($city === null) {
            throw CityNotFoundException::forId($id);
        }

        return $city;
    }

    public function getByName(string $name): City
    {
        $cities = $this->cityRepository->findAllByName($name);

        if (count($cities) === 0) {
            throw CityNotFoundException::forName($name);
        }

        if (count($cities) > 1) {
            throw AmbiguousCityNameException::forName($name, $cities);
        }

        return $cities[0];
    }

    /** @return City[] */
    public function findAll(): array
    {
        return $this->cityRepository->findAll();
    }

    public function create(CityData $data): City
    {
        $existingCity = $this->cityRepository->findByCoordinates($data->coordinates->latitude, $data->coordinates->longitude);

        if ($existingCity !== null) {
            throw DuplicateCityCoordinatesException::forCoordinates($data->coordinates->latitude, $data->coordinates->longitude);
        }

        $city = new City($data->name, $data->coordinates);
        $this->cityRepository->save($city);

        return $city;
    }

    public function update(int $id, CityData $data): City
    {
        $city = $this->get($id);
        $oldCoordinates = $city->coordinates;

        $existingCity = $this->cityRepository->findByCoordinates($data->coordinates->latitude, $data->coordinates->longitude);
        if ($existingCity !== null && $existingCity !== $city) {
            throw DuplicateCityCoordinatesException::forCoordinates($data->coordinates->latitude, $data->coordinates->longitude);
        }

        $city->edit($data->name, $data->coordinates);
        $this->cityRepository->save($city);

        if ($oldCoordinates->latitude !== $data->coordinates->latitude || $oldCoordinates->longitude !== $data->coordinates->longitude) {
            $this->weatherService->invalidateForecast($oldCoordinates);
        }

        return $city;
    }

    public function remove(int $id): void
    {
        $this->cityRepository->remove($this->get($id));
    }
}
