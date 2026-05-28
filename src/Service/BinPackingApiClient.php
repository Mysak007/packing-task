<?php

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Exception\ApiUnavailableException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

class BinPackingApiClient
{
    private const ENDPOINT = 'https://binpacking.janedbal.cz/api/v1/pack';
    private const SCALE = 1000;

    public function __construct(private readonly ClientInterface $httpClient)
    {
    }

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    public function findSmallestContainerId(array $products, array $boxes): ?int
    {
        $response = $this->callPackApi($products, $boxes);
        $payload = $this->decodeJson((string) $response->getBody());

        $packedContainers = $payload['packedContainers'] ?? null;
        if (!is_array($packedContainers)) {
            throw new ApiUnavailableException('Invalid API response shape.');
        }

        $targetItemsCount = count($products);
        $bestId = null;
        $bestVolume = null;

        foreach ($packedContainers as $packedContainer) {
            if (!is_array($packedContainer)) {
                continue;
            }

            $containerId = $packedContainer['containerId'] ?? null;
            $items = $packedContainer['items'] ?? null;
            if (!is_string($containerId) || !is_array($items) || count($items) !== $targetItemsCount) {
                continue;
            }

            $boxId = $this->containerIdToBoxId($containerId);
            if ($boxId === null) {
                continue;
            }

            $box = $this->findPackagingById($boxes, $boxId);
            if (!$box instanceof Packaging) {
                continue;
            }

            if ($bestVolume === null || $box->getVolume() < $bestVolume) {
                $bestId = $boxId;
                $bestVolume = $box->getVolume();
            }
        }

        return $bestId;
    }

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    private function callPackApi(array $products, array $boxes): ResponseInterface
    {
        $containers = array_map(
            fn (Packaging $box): array => [
                'id' => $this->boxIdToContainerId($box->getId()),
                'width' => $this->toApiInt($box->getWidth()),
                'length' => $this->toApiInt($box->getLength()),
                'depth' => $this->toApiInt($box->getHeight()),
                'maxWeight' => $this->toApiInt($box->getMaxWeight()),
            ],
            $boxes
        );

        $items = [];
        foreach ($products as $index => $product) {
            $items[] = [
                'id' => 'item-' . $index,
                'width' => $this->toApiInt($product->getWidth()),
                'length' => $this->toApiInt($product->getLength()),
                'depth' => $this->toApiInt($product->getHeight()),
                'weight' => $this->toApiInt($product->getWeight()),
            ];
        }

        try {
            $response = $this->httpClient->request('POST', self::ENDPOINT, [
                'json' => [
                    'containers' => $containers,
                    'items' => $items,
                ],
                'timeout' => 2.0,
                'connect_timeout' => 1.0,
                'http_errors' => false,
            ]);
        } catch (ConnectException|GuzzleException $exception) {
            throw new ApiUnavailableException('Packing API request failed.', 0, $exception);
        }

        $statusCode = $response->getStatusCode();
        if ($statusCode === 429 || $statusCode >= 500) {
            throw new ApiUnavailableException('Packing API unavailable with status ' . $statusCode . '.');
        }

        if ($statusCode >= 400) {
            throw new ApiUnavailableException('Packing API failed with non-retryable status ' . $statusCode . '.');
        }

        return $response;
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
            throw new ApiUnavailableException('Packing API returned invalid JSON.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new ApiUnavailableException('Packing API returned invalid payload.');
        }

        return $decoded;
    }

    private function toApiInt(float $value): int
    {
        return max(1, (int) round($value * self::SCALE));
    }

    private function boxIdToContainerId(?int $id): string
    {
        return 'box-' . (string) $id;
    }

    private function containerIdToBoxId(string $containerId): ?int
    {
        if (!str_starts_with($containerId, 'box-')) {
            return null;
        }

        $value = substr($containerId, 4);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param list<Packaging> $boxes
     */
    private function findPackagingById(array $boxes, int $id): ?Packaging
    {
        foreach ($boxes as $box) {
            if ($box->getId() === $id) {
                return $box;
            }
        }

        return null;
    }
}
