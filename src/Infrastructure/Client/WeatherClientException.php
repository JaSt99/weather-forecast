<?php

declare(strict_types=1);

namespace App\Infrastructure\Client;

class WeatherClientException extends \RuntimeException
{
    public static function connectionFailed(\Throwable $previous): self
    {
        return new self('Weather service is unavailable.', previous: $previous);
    }

    public static function unexpectedResponse(\Throwable $previous): self
    {
        return new self('Weather service returned an unexpected response.', previous: $previous);
    }
}
