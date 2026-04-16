<?php

declare(strict_types=1);

namespace App\Http\Dto;

class TemperatureEntry
{
    public string $date;
    public float $min;
    public float $max;
}
