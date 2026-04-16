<?php

declare(strict_types=1);

namespace App\Http\Controller\V1;

use App\Application\City\Port\CityServiceInterface;
use App\Application\Weather\Port\WeatherForecastServiceInterface;
use App\Domain\City\Exception\AmbiguousCityNameException;
use App\Domain\City\Exception\CityNotFoundException;
use App\Domain\Shared\ValueObject\Coordinates;
use App\Http\Dto\CoordinatesRequest;
use App\Http\Dto\WeatherRequest;
use App\Http\Dto\WeatherResponse;
use App\Http\Factory\CityCandidateResponseFactory;
use App\Http\Factory\WeatherResponseFactory;
use App\Infrastructure\Client\WeatherClientException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/weather')]
#[OA\Tag(name: 'Weather')]
class WeatherController extends AbstractController
{
    public function __construct(
        private readonly CityServiceInterface $cityService,
        private readonly WeatherForecastServiceInterface $weatherService,
        private readonly WeatherResponseFactory $weatherResponseFactory,
        private readonly CityCandidateResponseFactory $cityCandidateResponseFactory,
    ) {
    }

    #[Route('/forecast/city', name: 'api_forecast_city', methods: ['POST'])]
    #[OA\Post(
        path: '/api/weather/forecast/city',
        summary: 'Get 7-day weather forecast for a city',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: WeatherRequest::class))),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Weather forecast', content: new OA\JsonContent(ref: new Model(type: WeatherResponse::class))),
            new OA\Response(response: Response::HTTP_MULTIPLE_CHOICES, description: 'Multiple cities found — disambiguate by ID'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'City not found'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_SERVICE_UNAVAILABLE, description: 'Weather service unavailable'),
        ],
    )]
    public function forecastByCity(#[MapRequestPayload] WeatherRequest $request): JsonResponse
    {
        try {
            $city = $this->cityService->getByName($request->city);
            $forecast = $this->weatherService->getForecastForCoordinates($city->coordinates);

            return $this->json($this->weatherResponseFactory->create($request->city, $forecast));
        } catch (AmbiguousCityNameException $e) {
            $candidates = $this->cityCandidateResponseFactory->createCollection($e->getCities());

            return $this->json(['message' => $e->getMessage(), 'cities' => $candidates], Response::HTTP_MULTIPLE_CHOICES);
        } catch (CityNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (WeatherClientException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    #[Route('/forecast/city/{id}', name: 'api_forecast_city_by_id', methods: ['GET'])]
    #[OA\Get(
        path: '/api/weather/forecast/city/{id}',
        summary: 'Get 7-day weather forecast for a city by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Weather forecast', content: new OA\JsonContent(ref: new Model(type: WeatherResponse::class))),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'City not found'),
            new OA\Response(response: Response::HTTP_SERVICE_UNAVAILABLE, description: 'Weather service unavailable'),
        ],
    )]
    public function forecastByCityId(int $id): JsonResponse
    {
        try {
            $city = $this->cityService->get($id);
            $forecast = $this->weatherService->getForecastForCoordinates($city->coordinates);

            return $this->json($this->weatherResponseFactory->create($city->name, $forecast));
        } catch (CityNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (WeatherClientException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    #[Route('/forecast/coordinates', name: 'api_forecast_coordinates', methods: ['POST'])]
    #[OA\Post(
        path: '/api/weather/forecast/coordinates',
        summary: 'Get 7-day weather forecast for coordinates',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: CoordinatesRequest::class))),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Weather forecast', content: new OA\JsonContent(ref: new Model(type: WeatherResponse::class))),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
            new OA\Response(response: Response::HTTP_SERVICE_UNAVAILABLE, description: 'Weather service unavailable'),
        ],
    )]
    public function forecastByCoordinates(#[MapRequestPayload] CoordinatesRequest $request): JsonResponse
    {
        try {
            $coordinates = new Coordinates($request->latitude, $request->longitude);
            $forecast = $this->weatherService->getForecastForCoordinates($coordinates);

            return $this->json($this->weatherResponseFactory->create(
                sprintf('%s, %s', $forecast->latitude, $forecast->longitude),
                $forecast,
            ));
        } catch (WeatherClientException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
