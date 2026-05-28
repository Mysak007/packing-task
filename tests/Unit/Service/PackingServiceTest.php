<?php

namespace Tests\Unit\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Entity\PackingCache;
use App\Exception\BinPackingApiClientException;
use App\Exception\BinPackingApiTransportException;
use App\Repository\PackagingRepository;
use App\Repository\PackingCacheRepository;
use App\Service\BinPackingApiClient;
use App\Service\FallbackPackingCalculator;
use App\Service\PackingService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class PackingServiceTest extends TestCase
{
    public function testReturnsCachedBoxWithoutApiCall(): void
    {
        $box = $this->packaging(2, 4, 4, 4, 20);
        $cache = new PackingCache('hash', 2);

        $packagingRepo = $this->createMock(PackagingRepository::class);
        $packagingRepo->method('findAll')->willReturn([$box]);
        $packagingRepo->method('findById')->with(2)->willReturn($box);

        $cacheRepo = $this->createMock(PackingCacheRepository::class);
        $cacheRepo->method('findForInput')->willReturn($cache);

        $apiClient = $this->createMock(BinPackingApiClient::class);
        $apiClient->expects(self::never())->method('findSmallestBox');

        $fallback = $this->createMock(FallbackPackingCalculator::class);
        $fallback->expects(self::never())->method('findSmallestBox');

        $service = new PackingService($packagingRepo, $cacheRepo, $apiClient, $fallback);
        $result = $service->findSmallestBox([new ProductInput(1, 1, 1, 1)]);

        self::assertSame(2, $result?->getId());
    }

    public function testFallsBackOnRecoverableApiFailure(): void
    {
        $box = $this->packaging(1, 4, 4, 4, 20);

        $packagingRepo = $this->createMock(PackagingRepository::class);
        $packagingRepo->method('findAll')->willReturn([$box]);
        $packagingRepo->method('findById')->willReturn($box);

        $cacheRepo = $this->createMock(PackingCacheRepository::class);
        $cacheRepo->method('findForInput')->willReturn(null);
        $cacheRepo->expects(self::never())->method('saveForInput');

        $apiClient = $this->createMock(BinPackingApiClient::class);
        $apiClient->method('findSmallestBox')->willThrowException(
            new BinPackingApiTransportException('timeout')
        );

        $fallback = $this->createMock(FallbackPackingCalculator::class);
        $fallback->method('findSmallestBox')->willReturn(1);

        $service = new PackingService($packagingRepo, $cacheRepo, $apiClient, $fallback);
        $result = $service->findSmallestBox([new ProductInput(1, 1, 1, 1)]);

        self::assertSame(1, $result?->getId());
    }

    public function testRethrowsNonRecoverableApiFailure(): void
    {
        $box = $this->packaging(1, 4, 4, 4, 20);

        $packagingRepo = $this->createMock(PackagingRepository::class);
        $packagingRepo->method('findAll')->willReturn([$box]);

        $cacheRepo = $this->createMock(PackingCacheRepository::class);
        $cacheRepo->method('findByInputHash')->willReturn(null);

        $apiClient = $this->createMock(BinPackingApiClient::class);
        $apiClient->method('findSmallestBox')->willThrowException(
            new BinPackingApiClientException(422)
        );

        $fallback = $this->createMock(FallbackPackingCalculator::class);
        $fallback->expects(self::never())->method('findSmallestBox');

        $service = new PackingService($packagingRepo, $cacheRepo, $apiClient, $fallback);

        $this->expectException(BinPackingApiClientException::class);
        $service->findSmallestBox([new ProductInput(1, 1, 1, 1)]);
    }

    private function packaging(int $id, float $w, float $h, float $l, float $maxWeight): Packaging
    {
        $packaging = new Packaging($w, $h, $l, $maxWeight);
        $idProperty = new ReflectionProperty(Packaging::class, 'id');
        $idProperty->setValue($packaging, $id);

        return $packaging;
    }
}
