<?php

declare(strict_types=1);

namespace App\Http\Dto;

class CityCandidateResponse
{
    public int $id;
    public string $name;
    public float $latitude;
    public float $longitude;
}
