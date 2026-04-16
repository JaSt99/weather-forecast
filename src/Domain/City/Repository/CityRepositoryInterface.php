<?php

declare(strict_types=1);

namespace App\Domain\City\Repository;

use App\Domain\City\Entity\City;

interface CityRepositoryInterface
{
    public function find(int $id): ?City;

    public function findByName(string $name): ?City;

    /** @return City[] */
    public function findAllByName(string $name): array;

    public function findByCoordinates(float $latitude, float $longitude): ?City;

    /** @return City[] */
    public function findAll(): array;

    public function save(City $city): void;

    public function remove(City $city): void;
}
