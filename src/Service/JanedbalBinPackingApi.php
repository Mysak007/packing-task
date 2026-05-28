<?php declare(strict_types = 1);

namespace App\Service;

use App\Exception\BinPackingApiClientException;
use App\Exception\BinPackingApiRateLimitedException;
use App\Exception\BinPackingApiResponseException;
use App\Exception\BinPackingApiServerException;
use App\Exception\BinPackingApiTransportException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use function is_array;
use function json_decode;
use const JSON_THROW_ON_ERROR;

final readonly class JanedbalBinPackingApi implements BinPackingApi
{

    private const string ENDPOINT = 'https://binpacking.janedbal.cz/api/v1/pack';

    public function __construct(private ClientInterface $httpClient)
    {
    }

    /**
     * @param list<array<string, mixed>> $containers
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed>
     */
    public function pack(
        array $containers,
        array $items,
    ): array
    {
        try {
            $response = $this->httpClient->request('POST', self::ENDPOINT, [
                'json' => [
                    'containers' => $containers,
                    'items' => $items,
                ],
            ]);
        } catch (ConnectException $exception) {
            throw new BinPackingApiTransportException('Packing API connection failed.', $exception);
        } catch (RequestException $exception) {
            $errorResponse = $exception->getResponse();
            if ($errorResponse instanceof ResponseInterface) {
                $this->throwForHttpStatus($errorResponse->getStatusCode());
            }

            throw new BinPackingApiTransportException('Packing API request failed.', $exception);
        } catch (GuzzleException $exception) {
            throw new BinPackingApiTransportException('Packing API request failed.', $exception);
        }

        $this->throwForHttpStatus($response->getStatusCode());

        return $this->decodeJson((string) $response->getBody());
    }

    private function throwForHttpStatus(int $statusCode): void
    {
        if ($statusCode === 429) {
            throw new BinPackingApiRateLimitedException($statusCode);
        }

        if ($statusCode >= 500) {
            throw new BinPackingApiServerException($statusCode);
        }

        if ($statusCode >= 400) {
            throw new BinPackingApiClientException($statusCode);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $body): array
    {
        try {
            /** @var mixed $decoded */
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BinPackingApiResponseException('Packing API returned invalid JSON.', $exception);
        }

        if (!is_array($decoded)) {
            throw new BinPackingApiResponseException('Packing API returned invalid payload.');
        }

        /** @var array<string, mixed> $decoded */
        return $decoded;
    }

}
