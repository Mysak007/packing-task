<?php

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Exception\ApiUnavailableException;
use App\Repository\PackagingRepository;
use App\Repository\PackingCacheRepository;

class PackingService
{
    public function __construct(
        private readonly PackagingRepository $packagingRepository,
        private readonly PackingCacheRepository $cacheRepository,
        private readonly BinPackingApiClient $apiClient,
        private readonly FallbackPackingCalculator $fallbackCalculator
    ) {
    }

    /**
     * @param list<ProductInput> $products
     */
    public function findSmallestBox(array $products): ?Packaging
    {
        $boxes = $this->packagingRepository->findAll();
        if ($boxes === []) {
            return null;
        }

        $hash = $this->buildInputHash($products, $boxes);

        $cached = $this->cacheRepository->findByInputHash($hash);
        if ($cached !== null) {
            $packagingId = $cached->getPackagingId();
            if ($packagingId === null) {
                return null;
            }

            return $this->packagingRepository->findById($packagingId);
        }

        try {
            $packagingId = $this->apiClient->findSmallestContainerId($products, $boxes);
            $this->cacheRepository->saveResult($hash, $packagingId);

            if ($packagingId === null) {
                return null;
            }

            return $this->packagingRepository->findById($packagingId);
        } catch (ApiUnavailableException) {
            $fallbackPackagingId = $this->fallbackCalculator->findSmallestContainerId($products, $boxes);
            if ($fallbackPackagingId === null) {
                return null;
            }

            return $this->packagingRepository->findById($fallbackPackagingId);
        }
    }

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    private function buildInputHash(array $products, array $boxes): string
    {
        $normalizedProducts = array_map(function (ProductInput $product): array {
            $dims = $product->getNormalizedDimensions();

            return [$dims[0], $dims[1], $dims[2], $product->getWeight()];
        }, $products);

        usort($normalizedProducts, static fn (array $left, array $right): int => $left <=> $right);

        $normalizedBoxes = array_map(function (Packaging $box): array {
            $dims = [$box->getWidth(), $box->getHeight(), $box->getLength()];
            sort($dims, SORT_NUMERIC);

            return [$dims[0], $dims[1], $dims[2], $box->getMaxWeight()];
        }, $boxes);

        usort($normalizedBoxes, static fn (array $left, array $right): int => $left <=> $right);

        $canonical = [
            'products' => $normalizedProducts,
            'boxes' => $normalizedBoxes,
        ];

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
    }
}
