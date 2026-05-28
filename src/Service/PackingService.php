<?php declare(strict_types = 1);

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Entity\PackingCache;
use App\Exception\RecoverablePackingException;
use App\Infrastructure\ErrorLogger;
use App\Repository\PackagingRepository;
use App\Repository\PackingCacheRepository;

class PackingService
{

    public function __construct(
        private readonly PackagingRepository $packagingRepository,
        private readonly PackingCacheRepository $cacheRepository,
        private readonly BinPackingApiClient $apiClient,
        private readonly FallbackPackingCalculator $fallbackCalculator,
        private readonly ErrorLogger $errorLogger,
    )
    {
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
        if ($cached instanceof PackingCache) {
            return $this->resolvePackaging($cached->getPackagingId());
        }

        try {
            $packagingId = $this->apiClient->findSmallestBox($products, $boxes);
            $this->cacheRepository->saveForInput($products, $boxes, $packagingId);

            return $this->resolvePackaging($packagingId);
        } catch (RecoverablePackingException $recoverablePackingException) {
            $this->errorLogger->log($recoverablePackingException, ['fallback' => true]);
            return $this->resolvePackaging(
                $this->fallbackCalculator->findSmallestBox($products, $boxes),
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
