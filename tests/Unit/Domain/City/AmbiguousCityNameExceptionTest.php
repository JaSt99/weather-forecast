<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\City;

use App\Domain\City\Entity\City;
use App\Domain\City\Exception\AmbiguousCityNameException;
use App\Domain\Shared\ValueObject\Coordinates;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AmbiguousCityNameExceptionTest extends TestCase
{
    #[Test]
    public function forNameContainsNameInMessage(): void
    {
        $exception = AmbiguousCityNameException::forName('Springfield', []);

        $this->assertSame('Multiple cities found for name "Springfield".', $exception->getMessage());
    }

    #[Test]
    public function forNameReturnsCities(): void
    {
        $cities = [
            new City('Springfield', new Coordinates(39.7817, -89.6501)),
            new City('Springfield', new Coordinates(37.2153, -93.2982)),
        ];

        $exception = AmbiguousCityNameException::forName('Springfield', $cities);

        $this->assertSame($cities, $exception->getCities());
    }

    #[Test]
    public function isRuntimeException(): void
    {
        $exception = AmbiguousCityNameException::forName('X', []);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
