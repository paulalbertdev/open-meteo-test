<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\FavoriteSearch;
use App\Repository\FavoriteSearchRepository;
use App\Service\OpenMeteoClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class WeatherController extends AbstractController
{
    #[Route('/api/weather', methods: ['GET'])]
    public function weather(Request $request, OpenMeteoClient $client): JsonResponse
    {
        $city = trim((string) $request->query->get('city', ''));
        $latitude = $this->parseFloat($request->query->get('lat'));
        $longitude = $this->parseFloat($request->query->get('lon'));

        if ($city !== '') {
            $geo = $client->geocodeCity($city);
            if ($geo === null) {
                return $this->json(['error' => 'City not found.'], JsonResponse::HTTP_NOT_FOUND);
            }
            $latitude = $geo->latitude;
            $longitude = $geo->longitude;
            $label = $geo->label;
        } else {
            if ($latitude === null || $longitude === null) {
                return $this->json(['error' => 'Provide a city or valid coordinates.'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $label = sprintf('%.4f, %.4f', $latitude, $longitude);
        }

        $forecast = $client->fetchForecast($latitude, $longitude);

        return $this->json([
            'location' => [
                'label' => $label,
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
            'current' => $forecast['current'] ?? null,
            'daily' => $forecast['daily'] ?? null,
            'timezone' => $forecast['timezone'] ?? null,
        ]);
    }

    #[Route('/api/favorites', methods: ['GET'])]
    public function listFavorites(FavoriteSearchRepository $repository): JsonResponse
    {
        $favorites = $repository->findBy([], ['createdAt' => 'DESC']);

        $payload = array_map(fn(FavoriteSearch $favorite) => $this->favoriteToArray($favorite), $favorites);

        return $this->json($payload);
    }

    #[Route('/api/favorites', methods: ['POST'])]
    public function createFavorite(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $payload = $this->decodeJson($request);
        if ($payload === null) {
            return $this->json(['error' => 'Invalid JSON body.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $label = isset($payload['label']) ? trim((string) $payload['label']) : '';
        $latitude = $this->parseFloat($payload['latitude'] ?? null);
        $longitude = $this->parseFloat($payload['longitude'] ?? null);

        if ($label === '' || $latitude === null || $longitude === null) {
            return $this->json(['error' => 'Label, latitude, and longitude are required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $favorite = new FavoriteSearch($label, $latitude, $longitude);
        $entityManager->persist($favorite);
        $entityManager->flush();

        return $this->json($this->favoriteToArray($favorite), JsonResponse::HTTP_CREATED);
    }

    #[Route('/api/favorites/{id}', methods: ['DELETE'])]
    public function deleteFavorite(int $id, FavoriteSearchRepository $repository, EntityManagerInterface $entityManager): JsonResponse
    {
        $favorite = $repository->find($id);
        if ($favorite === null) {
            return $this->json(['error' => 'Favorite not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($favorite);
        $entityManager->flush();

        return $this->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * @return array<string, mixed>
     */
    private function favoriteToArray(FavoriteSearch $favorite): array
    {
        return [
            'id' => $favorite->getId(),
            'label' => $favorite->getLabel(),
            'latitude' => $favorite->getLatitude(),
            'longitude' => $favorite->getLongitude(),
            'createdAt' => $favorite->getCreatedAt()->format(DATE_ATOM),
        ];
    }

    private function parseFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $result = filter_var($value, FILTER_VALIDATE_FLOAT);
        if ($result === false) {
            return null;
        }

        return (float) $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(Request $request): ?array
    {
        $content = trim($request->getContent());
        if ($content === '') {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        return $data;
    }
}
