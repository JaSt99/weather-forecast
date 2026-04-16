<?php

declare(strict_types=1);

namespace App\Application\City\Port;

use App\Application\City\Dto\CityData;
use App\Domain\City\Entity\City;

interface CityServiceInterface
{
    public function get(int $id): City;

    public function getByName(string $name): City;

    /** @return City[] */
    public function findAll(): array;

    public function create(CityData $data): City;

    public function update(int $id, CityData $data): City;

    public function remove(int $id): void;
}
