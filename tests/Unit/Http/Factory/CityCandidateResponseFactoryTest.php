<?php

declare(strict_types=1);

namespace App\Tests\Unit\Http\Factory;

use App\Domain\City\Entity\City;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Http\Dto\CityCandidateResponse;
use App\Http\Factory\CityCandidateResponseFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CityCandidateResponseFactoryTest extends TestCase
{
    private CityCandidateResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new CityCandidateResponseFactory();
    }

    #[Test]
    public function createMapsCityToResponse(): void
    {
        $city = $this->buildCity(7, 'Praha', 50.0755, 14.4378);

        $result = $this->factory->create($city);

        $this->assertInstanceOf(CityCandidateResponse::class, $result);
        $this->assertSame(7, $result->id);
        $this->assertSame('Praha', $result->name);
        $this->assertSame(50.0755, $result->latitude);
        $this->assertSame(14.4378, $result->longitude);
    }

    #[Test]
    public function createCollectionMapsAllCities(): void
    {
        $cities = [
            $this->buildCity(1, 'Springfield', 39.7817, -89.6501),
            $this->buildCity(2, 'Springfield', 37.2153, -93.2982),
        ];

        $result = $this->factory->createCollection($cities);

        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(CityCandidateResponse::class, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame(2, $result[1]->id);
    }

    #[Test]
    public function createCollectionReturnsEmptyArrayForNoCities(): void
    {
        $this->assertSame([], $this->factory->createCollection([]));
    }

    private function buildCity(int $id, string $name, float $lat, float $lon): City
    {
        $city = new City($name, new Coordinates($lat, $lon));
        $ref = new \ReflectionProperty(City::class, 'id');
        $ref->setValue($city, $id);

        return $city;
    }
}
