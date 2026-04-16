<?php

declare(strict_types=1);

namespace App\Tests\Unit\Http\Factory;

use App\Domain\City\Entity\City;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Http\Dto\CityResponse;
use App\Http\Factory\CityResponseFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CityResponseFactoryTest extends TestCase
{
    private CityResponseFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new CityResponseFactory();
    }

    #[Test]
    public function createMapsCityToResponse(): void
    {
        $city = $this->createCityWithId(42, 'Praha', 50.0755, 14.4378);

        $result = $this->factory->create($city);

        $this->assertInstanceOf(CityResponse::class, $result);
        $this->assertSame(42, $result->id);
        $this->assertSame('Praha', $result->name);
        $this->assertSame(50.0755, $result->latitude);
        $this->assertSame(14.4378, $result->longitude);
    }

    #[Test]
    public function createFormatsTimestampsAsAtom(): void
    {
        $city = $this->createCityWithId(1, 'Brno', 49.1951, 16.6068);

        $result = $this->factory->create($city);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $result->createdAt,
        );
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            $result->updatedAt,
        );
    }

    private function createCityWithId(int $id, string $name, float $lat, float $lon): City
    {
        $city = new City($name, new Coordinates($lat, $lon));

        $reflection = new \ReflectionProperty(City::class, 'id');
        $reflection->setValue($city, $id);

        return $city;
    }
}
