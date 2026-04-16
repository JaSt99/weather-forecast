<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

readonly class Coordinates
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }
}
