<?php

namespace App;

use App\Exception\BadRequestException;
use App\Exception\ValidationException;
use App\Repository\PackagingRepository;
use App\Repository\PackingCacheRepository;
use App\Service\BinPackingApiClient;
use App\Service\FallbackPackingCalculator;
use App\Service\PackingService;
use App\Validator\PackingRequestValidator;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class Application
{

    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        $validator = new PackingRequestValidator();
        $packagingRepository = new PackagingRepository($this->entityManager);
        $cacheRepository = new PackingCacheRepository($this->entityManager);
        $apiClient = new BinPackingApiClient(new Client());
        $fallback = new FallbackPackingCalculator();
        $packingService = new PackingService($packagingRepository, $cacheRepository, $apiClient, $fallback);

        try {
            $products = $validator->validate($request);
            $box = $packingService->findSmallestBox($products);

            $payload = [
                'box' => $box === null ? null : [
                    'id' => $box->getId(),
                    'width' => $box->getWidth(),
                    'height' => $box->getHeight(),
                    'length' => $box->getLength(),
                    'maxWeight' => $box->getMaxWeight(),
                ],
            ];

            return $this->jsonResponse(200, $payload);
        } catch (BadRequestException $exception) {
            return $this->jsonResponse(400, [
                'error' => 'bad_request',
                'message' => $exception->getMessage(),
            ]);
        } catch (ValidationException $exception) {
            return $this->jsonResponse(422, [
                'error' => 'validation_failed',
                'message' => $exception->getMessage(),
                'violations' => $exception->getViolations(),
            ]);
        } catch (Throwable $exception) {
            return $this->jsonResponse(500, [
                'error' => 'internal_error',
                'message' => 'An unexpected error occurred.',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(int $status, array $payload): ResponseInterface
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $json = '{"error":"internal_error","message":"An unexpected error occurred."}';
            $status = 500;
        }

        return new Response($status, ['Content-Type' => 'application/json'], $json);
    }

}
