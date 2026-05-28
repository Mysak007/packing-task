<?php

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Exception\RecoverablePackingException;
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

        $cached = $this->cacheRepository->findForInput($products, $boxes);
        if ($cached !== null) {
            return $this->resolvePackaging($cached->getPackagingId());
        }

        try {
            $packagingId = $this->apiClient->findSmallestBox($products, $boxes);
            $this->cacheRepository->saveForInput($products, $boxes, $packagingId);

            return $this->resolvePackaging($packagingId);
        } catch (RecoverablePackingException) {
            return $this->resolvePackaging(
                $this->fallbackCalculator->findSmallestBox($products, $boxes)
            );
        }
    }

    private function resolvePackaging(?int $packagingId): ?Packaging
    {
        if ($packagingId === null) {
            return null;
        }

        return $this->packagingRepository->findById($packagingId);
    }
}
