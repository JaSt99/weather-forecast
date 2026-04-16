<?php

declare(strict_types=1);

namespace App\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CityRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotNull]
    #[Assert\Range(min: -90, max: 90)]
    public float $latitude;

    #[Assert\NotNull]
    #[Assert\Range(min: -180, max: 180)]
    public float $longitude;
}
