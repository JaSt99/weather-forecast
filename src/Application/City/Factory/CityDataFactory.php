<?php

declare(strict_types=1);

namespace App\Application\City\Factory;

use App\Application\City\Dto\CityData;
use App\Domain\Shared\ValueObject\Coordinates;

final class CityDataFactory
{
    public function create(string $name, float $latitude, float $longitude): CityData
    {
        return new CityData($name, new Coordinates($latitude, $longitude));
    }
}
