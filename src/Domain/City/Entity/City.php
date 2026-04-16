<?php

declare(strict_types=1);

namespace App\Domain\City\Entity;

use App\Domain\Shared\ValueObject\Coordinates;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'city')]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private(set) int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private(set) string $name;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private(set) float $latitude;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private(set) float $longitude;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private(set) \DateTimeImmutable $updatedAt;

    public Coordinates $coordinates {
        get => new Coordinates($this->latitude, $this->longitude);
    }

    public function __construct(string $name, Coordinates $coordinates)
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->setAttributes($name, $coordinates);
    }

    public function edit(string $name, Coordinates $coordinates): void
    {
        $this->setAttributes($name, $coordinates);
    }

    private function setAttributes(string $name, Coordinates $coordinates): void
    {
        $this->name = $name;
        $this->latitude = $coordinates->latitude;
        $this->longitude = $coordinates->longitude;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
