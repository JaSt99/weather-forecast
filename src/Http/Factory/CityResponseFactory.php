<?php

declare(strict_types=1);

namespace App\Http\Factory;

use App\Domain\City\Entity\City;
use App\Http\Dto\CityResponse;

final class CityResponseFactory
{
    public function create(City $city): CityResponse
    {
        $response = new CityResponse();
        $response->id = $city->id;
        $response->name = $city->name;
        $response->latitude = $city->latitude;
        $response->longitude = $city->longitude;
        $response->createdAt = $city->createdAt->format(\DateTimeInterface::ATOM);
        $response->updatedAt = $city->updatedAt->format(\DateTimeInterface::ATOM);

        return $response;
    }
}
