<?php

declare(strict_types=1);

namespace App\Domain\City\Exception;

use App\Domain\City\Entity\City;

class AmbiguousCityNameException extends \RuntimeException
{
    /** @param City[] $cities */
    private function __construct(
        private readonly array $cities,
        string $name,
    ) {
        parent::__construct(sprintf('Multiple cities found for name "%s".', $name));
    }

    /** @return City[] */
    public function getCities(): array
    {
        return $this->cities;
    }

    /** @param City[] $cities */
    public static function forName(string $name, array $cities): self
    {
        return new self($cities, $name);
    }
}
