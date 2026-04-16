<?php

declare(strict_types=1);

namespace App\Http\Dto;

class CityResponse
{
    public int $id;
    public string $name;
    public float $latitude;
    public float $longitude;
    public string $createdAt;
    public string $updatedAt;
}
