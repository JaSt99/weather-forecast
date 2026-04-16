<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\City;

use App\Domain\City\Exception\DuplicateCityCoordinatesException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class DuplicateCityCoordinatesExceptionTest extends TestCase
{
    #[Test]
    public function forCoordinatesContainsCoordinatesInMessage(): void
    {
        $exception = DuplicateCityCoordinatesException::forCoordinates(50.0755, 14.4378);

        $this->assertStringContainsString('50.0755', $exception->getMessage());
        $this->assertStringContainsString('14.4378', $exception->getMessage());
    }
}
