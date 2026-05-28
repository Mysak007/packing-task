<?php declare(strict_types = 1);

namespace App;

use App\Entity\Packaging;
use App\Exception\BadRequestException;
use App\Exception\BinPackingApiException;
use App\Exception\ValidationException;
use App\Infrastructure\ErrorLogger;
use App\Service\PackingService;
use App\Validator\PackingRequestValidator;
use GuzzleHttp\Psr7\Response;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;
use function json_encode;
use const JSON_THROW_ON_ERROR;

class Application
{

    public function __construct(
        private readonly PackingRequestValidator $validator,
        private readonly PackingService $packingService,
        private readonly ErrorLogger $errorLogger,
    )
    {
    }

    public function run(RequestInterface $request): ResponseInterface
    {
        try {
            $products = $this->validator->validate($request);
            $box = $this->packingService->findSmallestBox($products);

            $payload = [
                'box' => $box instanceof Packaging ? [
                    'id' => $box->getId(),
                    'width' => $box->getWidth(),
                    'height' => $box->getHeight(),
                    'length' => $box->getLength(),
                    'maxWeight' => $box->getMaxWeight(),
                ] : null,
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
        } catch (BinPackingApiException $exception) {
            $this->errorLogger->log($exception, [
                'statusCode' => $exception->getStatusCode(),
            ]);

            return $this->jsonResponse(502, [
                'error' => 'packing_service_unavailable',
                'message' => 'External packing service could not process the request.',
            ]);
        } catch (Throwable $exception) {
            $this->errorLogger->log($exception);

            return $this->jsonResponse(500, [
                'error' => 'internal_error',
                'message' => 'An unexpected error occurred.',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(
        int $status,
        array $payload,
    ): ResponseInterface
    {
        try {
            $json = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            $this->errorLogger->log($jsonException);
            $json = '{"error":"internal_error","message":"An unexpected error occurred."}';
            $status = 500;
        }

        return new Response($status, ['Content-Type' => 'application/json'], $json);
    }

}
