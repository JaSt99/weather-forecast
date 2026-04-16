<?php

declare(strict_types=1);

namespace App\Domain\City\Exception;

class CityNotFoundException extends \RuntimeException
{
    public static function forId(int $id): self
    {
        return new self(sprintf('City with ID %d not found.', $id));
    }

    public static function forName(string $name): self
    {
        return new self(sprintf('City "%s" not found.', $name));
    }
}
