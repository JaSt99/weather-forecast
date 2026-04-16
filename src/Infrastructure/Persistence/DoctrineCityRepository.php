<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\City\Entity\City;
use App\Domain\City\Repository\CityRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

readonly class DoctrineCityRepository implements CityRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function find(int $id): ?City
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(City::class, 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof City ? $result : null;
    }

    public function findByName(string $name): ?City
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(City::class, 'c')
            ->where('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof City ? $result : null;
    }

    /** @return City[] */
    public function findAllByName(string $name): array
    {
        /** @var City[] $result */
        $result = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(City::class, 'c')
            ->where('c.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function findByCoordinates(float $latitude, float $longitude): ?City
    {
        $result = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(City::class, 'c')
            ->where('c.latitude = :latitude')
            ->andWhere('c.longitude = :longitude')
            ->setParameter('latitude', $latitude)
            ->setParameter('longitude', $longitude)
            ->getQuery()
            ->getOneOrNullResult();

        return $result instanceof City ? $result : null;
    }

    /** @return City[] */
    public function findAll(): array
    {
        /** @var City[] $result */
        $result = $this->entityManager->createQueryBuilder()
            ->select('c')
            ->from(City::class, 'c')
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();

        return $result;
    }

    public function save(City $city): void
    {
        $this->entityManager->persist($city);
        $this->entityManager->flush();
    }

    public function remove(City $city): void
    {
        $this->entityManager->remove($city);
        $this->entityManager->flush();
    }
}
