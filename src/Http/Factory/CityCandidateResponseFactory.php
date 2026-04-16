<?php

declare(strict_types=1);

namespace App\Http\Factory;

use App\Domain\City\Entity\City;
use App\Http\Dto\CityCandidateResponse;

final class CityCandidateResponseFactory
{
    public function create(City $city): CityCandidateResponse
    {
        $response = new CityCandidateResponse();
        $response->id = $city->id;
        $response->name = $city->name;
        $response->latitude = $city->coordinates->latitude;
        $response->longitude = $city->coordinates->longitude;

        return $response;
    }

    /**
     * @param City[] $cities
     * @return CityCandidateResponse[]
     */
    public function createCollection(array $cities): array
    {
        return array_map($this->create(...), $cities);
    }
}
