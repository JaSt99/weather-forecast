<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\City;

use App\Domain\City\Exception\CityNotFoundException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CityNotFoundExceptionTest extends TestCase
{
    #[Test]
    public function forIdContainsIdInMessage(): void
    {
        $exception = CityNotFoundException::forId(42);

        $this->assertInstanceOf(CityNotFoundException::class, $exception);
        $this->assertSame('City with ID 42 not found.', $exception->getMessage());
    }

    #[Test]
    public function forNameContainsNameInMessage(): void
    {
        $exception = CityNotFoundException::forName('Atlantis');

        $this->assertInstanceOf(CityNotFoundException::class, $exception);
        $this->assertSame('City "Atlantis" not found.', $exception->getMessage());
    }

    #[Test]
    public function isRuntimeException(): void
    {
        $exception = CityNotFoundException::forId(1);

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
