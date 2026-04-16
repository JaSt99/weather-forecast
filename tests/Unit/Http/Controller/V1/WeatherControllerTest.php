<?php

declare(strict_types=1);

namespace App\Tests\Unit\Http\Controller\V1;

use App\Application\City\Port\CityServiceInterface;
use App\Domain\City\Entity\City;
use App\Domain\City\Exception\AmbiguousCityNameException;
use App\Domain\City\Exception\CityNotFoundException;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Domain\Weather\Dto\DayForecast;
use App\Domain\Weather\Dto\WeatherForecast;
use App\Infrastructure\Client\WeatherClientException;
use App\Application\Weather\Port\WeatherForecastServiceInterface;
use App\Http\Controller\V1\WeatherController;
use App\Http\Dto\CoordinatesRequest;
use App\Http\Dto\WeatherRequest;
use App\Http\Factory\CityCandidateResponseFactory;
use App\Http\Factory\WeatherResponseFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class WeatherControllerTest extends TestCase
{
    private CityServiceInterface&MockObject $cityService;
    private WeatherForecastServiceInterface&MockObject $weatherService;
    private WeatherController $controller;

    protected function setUp(): void
    {
        $this->cityService = $this->createMock(CityServiceInterface::class);
        $this->weatherService = $this->createMock(WeatherForecastServiceInterface::class);
        $this->controller = new WeatherController(
            $this->cityService,
            $this->weatherService,
            new WeatherResponseFactory(),
            new CityCandidateResponseFactory(),
        );
        $this->controller->setContainer(new Container());
    }

    #[Test]
    public function forecastByCityReturns200WithWeatherResponse(): void
    {
        $city = new City('Praha', new Coordinates(50.0755, 14.4378));
        $this->cityService->method('getByName')->with('Praha')->willReturn($city);
        $this->weatherService->method('getForecastForCoordinates')
            ->willReturn($this->buildForecast(50.0755, 14.4378));

        $response = $this->controller->forecastByCity($this->buildCityRequest('Praha'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertSame('Praha', $body['city']);
        $this->assertCount(2, $body['temperature']);
    }

    #[Test]
    public function forecastByCityResponseContainsTemperatureEntryFields(): void
    {
        $city = new City('Praha', new Coordinates(50.0755, 14.4378));
        $this->cityService->method('getByName')->willReturn($city);
        $this->weatherService->method('getForecastForCoordinates')
            ->willReturn($this->buildForecast(50.0755, 14.4378));

        $response = $this->controller->forecastByCity($this->buildCityRequest('Praha'));

        $entry = $this->decode($response)['temperature'][0];
        $this->assertArrayHasKey('date', $entry);
        $this->assertArrayHasKey('min', $entry);
        $this->assertArrayHasKey('max', $entry);
        $this->assertSame('2026-04-16', $entry['date']);
        $this->assertSame(8.5, $entry['min']);
        $this->assertSame(17.2, $entry['max']);
    }

    #[Test]
    public function forecastByCityReturns404WhenCityNotFound(): void
    {
        $this->cityService->method('getByName')
            ->willThrowException(CityNotFoundException::forName('Unknown'));

        $response = $this->controller->forecastByCity($this->buildCityRequest('Unknown'));

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertArrayHasKey('message', $body);
        $this->assertStringContainsString('Unknown', $body['message']);
    }

    #[Test]
    public function forecastByCityReturns503OnConnectionFailure(): void
    {
        $city = new City('Praha', new Coordinates(50.0755, 14.4378));
        $this->cityService->method('getByName')->willReturn($city);
        $this->weatherService->method('getForecastForCoordinates')
            ->willThrowException(WeatherClientException::connectionFailed(new \RuntimeException()));

        $response = $this->controller->forecastByCity($this->buildCityRequest('Praha'));

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
        $this->assertArrayHasKey('message', $this->decode($response));
    }

    #[Test]
    public function forecastByCityReturns503OnUnexpectedResponse(): void
    {
        $city = new City('Praha', new Coordinates(50.0755, 14.4378));
        $this->cityService->method('getByName')->willReturn($city);
        $this->weatherService->method('getForecastForCoordinates')
            ->willThrowException(WeatherClientException::unexpectedResponse(new \RuntimeException()));

        $response = $this->controller->forecastByCity($this->buildCityRequest('Praha'));

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
    }

    #[Test]
    public function forecastByCityReturns300WithCandidatesWhenMultipleCitiesFound(): void
    {
        $cities = [
            $this->buildCity(1, 'Springfield', 39.7817, -89.6501),
            $this->buildCity(2, 'Springfield', 37.2153, -93.2982),
        ];
        $this->cityService->method('getByName')
            ->willThrowException(AmbiguousCityNameException::forName('Springfield', $cities));

        $response = $this->controller->forecastByCity($this->buildCityRequest('Springfield'));

        $this->assertSame(Response::HTTP_MULTIPLE_CHOICES, $response->getStatusCode());
        $body = $this->decode($response);
        $this->assertArrayHasKey('message', $body);
        $this->assertCount(2, $body['cities']);
        $this->assertArrayHasKey('id', $body['cities'][0]);
        $this->assertArrayHasKey('name', $body['cities'][0]);
        $this->assertArrayHasKey('latitude', $body['cities'][0]);
        $this->assertArrayHasKey('longitude', $body['cities'][0]);
    }

    #[Test]
    public function forecastByCityIdReturns200WithWeatherResponse(): void
    {
        $city = $this->buildCity(5, 'Praha', 50.0755, 14.4378);
        $this->cityService->method('get')->with(5)->willReturn($city);
        $this->weatherService->method('getForecastForCoordinates')
            ->willReturn($this->buildForecast(50.0755, 14.4378));

        $response = $this->controller->forecastByCityId(5);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('Praha', $this->decode($response)['city']);
    }

    #[Test]
    public function forecastByCityIdReturns404WhenCityNotFound(): void
    {
        $this->cityService->method('get')->willThrowException(CityNotFoundException::forId(99));

        $response = $this->controller->forecastByCityId(99);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertArrayHasKey('message', $this->decode($response));
    }

    #[Test]
    public function forecastByCityIdReturns503OnWeatherClientException(): void
    {
        $city = $this->buildCity(5, 'Praha', 50.0755, 14.4378);
        $this->cityService->method('get')->willReturn($city);
        $this->weatherService->method('getForecastForCoordinates')
            ->willThrowException(WeatherClientException::connectionFailed(new \RuntimeException()));

        $response = $this->controller->forecastByCityId(5);

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
    }

    #[Test]
    public function forecastByCoordinatesReturns200(): void
    {
        $this->weatherService->method('getForecastForCoordinates')
            ->with($this->callback(fn (Coordinates $c) => $c->latitude === 50.08 && $c->longitude === 14.42))
            ->willReturn($this->buildForecast(50.08, 14.42));

        $response = $this->controller->forecastByCoordinates($this->buildCoordinatesRequest(50.08, 14.42));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('50.08, 14.42', $this->decode($response)['city']);
    }

    #[Test]
    public function forecastByCoordinatesReturns503OnWeatherClientException(): void
    {
        $this->weatherService->method('getForecastForCoordinates')
            ->willThrowException(WeatherClientException::connectionFailed(new \RuntimeException()));

        $response = $this->controller->forecastByCoordinates($this->buildCoordinatesRequest(50.08, 14.42));

        $this->assertSame(Response::HTTP_SERVICE_UNAVAILABLE, $response->getStatusCode());
    }

    private function buildForecast(float $lat, float $lon): WeatherForecast
    {
        return new WeatherForecast($lat, $lon, 'Europe/Prague', [
            new DayForecast('2026-04-16', 8.5, 17.2),
            new DayForecast('2026-04-17', 10.0, 20.5),
        ]);
    }

    private function buildCityRequest(string $city): WeatherRequest
    {
        $request = new WeatherRequest();
        $request->city = $city;

        return $request;
    }

    private function buildCoordinatesRequest(float $lat, float $lon): CoordinatesRequest
    {
        $request = new CoordinatesRequest();
        $request->latitude = $lat;
        $request->longitude = $lon;

        return $request;
    }

    private function buildCity(int $id, string $name, float $lat, float $lon): City
    {
        $city = new City($name, new Coordinates($lat, $lon));
        $ref = new \ReflectionProperty(City::class, 'id');
        $ref->setValue($city, $id);

        return $city;
    }

    private function decode(JsonResponse $response): array
    {
        return json_decode($response->getContent(), true);
    }
}
