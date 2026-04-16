<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Fixtures;

use App\Domain\City\Entity\City;
use App\Domain\Shared\ValueObject\Coordinates;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CityFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getCityData() as [$name, $latitude, $longitude]) {
            $manager->persist(new City($name, new Coordinates($latitude, $longitude)));
        }

        $manager->flush();
    }

    /** @return array<int, array{string, float, float}> */
    private function getCityData(): array
    {
        return [
            ['Praha', 50.0755381, 14.4378005],
            ['Brno', 49.1950602, 16.6068371],
            ['Ostrava', 49.8209226, 18.2625243],
            ['Olomouc', 49.5937964, 17.2508747],
            ['Plzeň', 49.7383858, 13.3736371],
            ['Pardubice', 50.0343092, 15.7811994],
        ];
    }
}
