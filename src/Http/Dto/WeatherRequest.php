<?php

declare(strict_types=1);

namespace App\Http\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class WeatherRequest
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public string $city;
}
