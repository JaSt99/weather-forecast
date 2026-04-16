<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Client;

use App\Infrastructure\Client\WeatherClientException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class WeatherClientExceptionTest extends TestCase
{
    #[Test]
    public function connectionFailedContainsExpectedMessage(): void
    {
        $previous = new \RuntimeException('Refused');
        $exception = WeatherClientException::connectionFailed($previous);

        $this->assertInstanceOf(WeatherClientException::class, $exception);
        $this->assertSame('Weather service is unavailable.', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function unexpectedResponseContainsExpectedMessage(): void
    {
        $previous = new \RuntimeException('500 Internal Server Error');
        $exception = WeatherClientException::unexpectedResponse($previous);

        $this->assertInstanceOf(WeatherClientException::class, $exception);
        $this->assertSame('Weather service returned an unexpected response.', $exception->getMessage());
        $this->assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function isRuntimeException(): void
    {
        $exception = WeatherClientException::connectionFailed(new \RuntimeException());

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
