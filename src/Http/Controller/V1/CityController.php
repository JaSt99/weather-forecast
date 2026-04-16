<?php

declare(strict_types=1);

namespace App\Http\Controller\V1;

use App\Application\City\Factory\CityDataFactory;
use App\Application\City\Port\CityServiceInterface;
use App\Domain\City\Exception\CityNotFoundException;
use App\Domain\City\Exception\DuplicateCityCoordinatesException;
use App\Http\Dto\CityRequest;
use App\Http\Dto\CityResponse;
use App\Http\Factory\CityResponseFactory;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/cities')]
#[OA\Tag(name: 'Cities')]
class CityController extends AbstractController
{
    public function __construct(
        private readonly CityServiceInterface $cityService,
        private readonly CityDataFactory $cityDataFactory,
        private readonly CityResponseFactory $cityResponseFactory,
    ) {
    }

    #[Route('', name: 'api_city_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/cities',
        summary: 'List all cities',
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'List of cities',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: CityResponse::class)),
                ),
            ),
        ],
    )]
    public function list(): JsonResponse
    {
        return $this->json(array_map($this->cityResponseFactory->create(...), $this->cityService->findAll()));
    }

    #[Route('/{id}', name: 'api_city_get', methods: ['GET'])]
    #[OA\Get(
        path: '/api/cities/{id}',
        summary: 'Get a city by ID',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'City detail', content: new OA\JsonContent(ref: new Model(type: CityResponse::class))),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'City not found'),
        ],
    )]
    public function get(int $id): JsonResponse
    {
        try {
            return $this->json($this->cityResponseFactory->create($this->cityService->get($id)));
        } catch (CityNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }
    }

    #[Route('', name: 'api_city_create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/cities',
        summary: 'Create a new city',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: CityRequest::class))),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'City created', content: new OA\JsonContent(ref: new Model(type: CityResponse::class))),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'City with given coordinates already exists'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
        ],
    )]
    public function create(#[MapRequestPayload] CityRequest $request): JsonResponse
    {
        try {
            $data = $this->cityDataFactory->create($request->name, $request->latitude, $request->longitude);
            $city = $this->cityService->create($data);

            return $this->json($this->cityResponseFactory->create($city), Response::HTTP_CREATED);
        } catch (DuplicateCityCoordinatesException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/{id}', name: 'api_city_update', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/cities/{id}',
        summary: 'Update a city',
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: new Model(type: CityRequest::class))),
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'City updated', content: new OA\JsonContent(ref: new Model(type: CityResponse::class))),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'City not found'),
            new OA\Response(response: Response::HTTP_CONFLICT, description: 'City with given coordinates already exists'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validation failed'),
        ],
    )]
    public function update(int $id, #[MapRequestPayload] CityRequest $request): JsonResponse
    {
        try {
            $data = $this->cityDataFactory->create($request->name, $request->latitude, $request->longitude);
            $city = $this->cityService->update($id, $data);

            return $this->json($this->cityResponseFactory->create($city));
        } catch (CityNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        } catch (DuplicateCityCoordinatesException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }
    }

    #[Route('/{id}', name: 'api_city_remove', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/cities/{id}',
        summary: 'Remove a city',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_NO_CONTENT, description: 'City removed'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'City not found'),
        ],
    )]
    public function remove(int $id): JsonResponse
    {
        try {
            $this->cityService->remove($id);
        } catch (CityNotFoundException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
