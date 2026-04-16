<?php

declare(strict_types=1);

namespace App\Tests\Unit\Http\Controller\V1;

use App\Application\City\Factory\CityDataFactory;
use App\Domain\City\Entity\City;
use App\Domain\City\Exception\CityNotFoundException;
use App\Domain\City\Exception\DuplicateCityCoordinatesException;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Http\Controller\V1\CityController;
use App\Http\Dto\CityRequest;
use App\Http\Factory\CityResponseFactory;
use App\Application\City\Port\CityServiceInterface;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CityControllerTest extends TestCase
{
    private CityServiceInterface&MockObject $cityService;
    private CityController $controller;

    protected function setUp(): void
    {
        $this->cityService = $this->createMock(CityServiceInterface::class);
        $this->controller = new CityController(
            $this->cityService,
            new CityDataFactory(),
            new CityResponseFactory(),
        );
        $this->controller->setContainer(new Container());
    }

    #[Test]
    public function listReturns200WithCityArray(): void
    {
        $this->cityService->method('findAll')->willReturn([
            $this->createCityWithId(1, 'Praha'),
            $this->createCityWithId(2, 'Brno'),
        ]);

        $response = $this->controller->list();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertCount(2, $body);
        $this->assertSame('Praha', $body[0]['name']);
        $this->assertSame('Brno', $body[1]['name']);
    }

    #[Test]
    public function listReturns200WithEmptyArray(): void
    {
        $this->cityService->method('findAll')->willReturn([]);

        $response = $this->controller->list();

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame([], $this->decode($response));
    }

    #[Test]
    public function getReturns200WithCity(): void
    {
        $this->cityService->method('get')->with(1)->willReturn($this->createCityWithId(1, 'Praha'));

        $response = $this->controller->get(1);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame(1, $body['id']);
        $this->assertSame('Praha', $body['name']);
    }

    #[Test]
    public function getReturns404WhenCityNotFound(): void
    {
        $this->cityService->method('get')->willThrowException(CityNotFoundException::forId(99));

        $response = $this->controller->get(99);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertArrayHasKey('message', $body);
        $this->assertStringContainsString('99', $body['message']);
    }

    #[Test]
    public function createReturns201WithCreatedCity(): void
    {
        $this->cityService->method('create')->willReturn($this->createCityWithId(3, 'Ostrava'));

        $response = $this->controller->create($this->buildRequest('Ostrava', 49.8209, 18.2625));

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame(3, $body['id']);
        $this->assertSame('Ostrava', $body['name']);
    }

    #[Test]
    public function createReturns409WhenCoordinatesAlreadyTaken(): void
    {
        $this->cityService->method('create')
            ->willThrowException(DuplicateCityCoordinatesException::forCoordinates(49.8209, 18.2625));

        $response = $this->controller->create($this->buildRequest('Ostrava', 49.8209, 18.2625));

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertArrayHasKey('message', $this->decode($response));
    }

    #[Test]
    public function updateReturns200WithUpdatedCity(): void
    {
        $this->cityService->method('update')->willReturn($this->createCityWithId(1, 'Praha-new'));

        $response = $this->controller->update(1, $this->buildRequest('Praha-new', 50.1, 14.5));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Praha-new', $this->decode($response)['name']);
    }

    #[Test]
    public function updateReturns404WhenCityNotFound(): void
    {
        $this->cityService->method('update')->willThrowException(CityNotFoundException::forId(99));

        $response = $this->controller->update(99, $this->buildRequest('x', 0.0, 0.0));

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertArrayHasKey('message', $this->decode($response));
    }

    #[Test]
    public function updateReturns409WhenCoordinatesAlreadyTaken(): void
    {
        $this->cityService->method('update')
            ->willThrowException(DuplicateCityCoordinatesException::forCoordinates(49.1951, 16.6068));

        $response = $this->controller->update(1, $this->buildRequest('Praha', 49.1951, 16.6068));

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertArrayHasKey('message', $this->decode($response));
    }

    #[Test]
    public function removeReturns204(): void
    {
        $this->cityService->expects($this->once())->method('remove')->with(5);

        $response = $this->controller->remove(5);

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
    }

    #[Test]
    public function removeReturns404WhenCityNotFound(): void
    {
        $this->cityService->method('remove')->willThrowException(CityNotFoundException::forId(99));

        $response = $this->controller->remove(99);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertArrayHasKey('message', $this->decode($response));
    }

    private function createCityWithId(int $id, string $name): City
    {
        $city = new City($name, new Coordinates(50.0, 14.0));

        $reflection = new \ReflectionProperty(City::class, 'id');
        $reflection->setValue($city, $id);

        return $city;
    }

    private function buildRequest(string $name, float $lat, float $lon): CityRequest
    {
        $request = new CityRequest();
        $request->name = $name;
        $request->latitude = $lat;
        $request->longitude = $lon;

        return $request;
    }

    private function decode(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true);
    }
}
