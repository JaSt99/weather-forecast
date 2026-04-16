<?php

declare(strict_types=1);

namespace App\Domain\City\Exception;

class DuplicateCityCoordinatesException extends \RuntimeException
{
    public static function forCoordinates(float $latitude, float $longitude): self
    {
        return new self(sprintf(
            'A city with coordinates [%s, %s] already exists.',
            $latitude,
            $longitude,
        ));
    }
}
