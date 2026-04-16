<?php

declare(strict_types=1);

namespace App\Application\City\Dto;

use App\Domain\Shared\ValueObject\Coordinates;

readonly class CityData
{
    public function __construct(
        public string $name,
        public Coordinates $coordinates,
    ) {
    }
}
