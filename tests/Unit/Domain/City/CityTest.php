<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\City;

use App\Domain\City\Entity\City;
use App\Domain\Shared\ValueObject\Coordinates;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CityTest extends TestCase
{
    #[Test]
    public function constructorSetsProperties(): void
    {
        $city = new City('Praha', new Coordinates(50.0755, 14.4378));

        $this->assertSame('Praha', $city->name);
        $this->assertSame(50.0755, $city->latitude);
        $this->assertSame(14.4378, $city->longitude);
    }

    #[Test]
    public function constructorSetsCreatedAtAndUpdatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $city = new City('Praha', new Coordinates(50.0, 14.0));
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $city->createdAt);
        $this->assertLessThanOrEqual($after, $city->createdAt);
        $this->assertGreaterThanOrEqual($before, $city->updatedAt);
        $this->assertLessThanOrEqual($after, $city->updatedAt);
    }

    #[Test]
    public function editUpdatesNameAndCoordinates(): void
    {
        $city = new City('Praha', new Coordinates(50.0755, 14.4378));

        $city->edit('Brno', new Coordinates(49.1951, 16.6068));

        $this->assertSame('Brno', $city->name);
        $this->assertSame(49.1951, $city->latitude);
        $this->assertSame(16.6068, $city->longitude);
    }

    #[Test]
    public function editUpdatesUpdatedAtButPreservesCreatedAt(): void
    {
        $city = new City('Praha', new Coordinates(50.0755, 14.4378));
        $originalCreatedAt = $city->createdAt;

        usleep(1000);

        $city->edit('Brno', new Coordinates(49.1951, 16.6068));

        $this->assertEquals($originalCreatedAt, $city->createdAt);
        $this->assertGreaterThanOrEqual($originalCreatedAt, $city->updatedAt);
    }

    #[Test]
    public function coordinatesVirtualPropertyReturnsCorrectValues(): void
    {
        $city = new City('Praha', new Coordinates(50.0755, 14.4378));

        $this->assertSame(50.0755, $city->coordinates->latitude);
        $this->assertSame(14.4378, $city->coordinates->longitude);
    }
}
