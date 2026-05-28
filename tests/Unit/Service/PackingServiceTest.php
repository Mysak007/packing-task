<?php

namespace Tests\Unit\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Entity\PackingCache;
use App\Exception\ApiUnavailableException;
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
        $cacheRepo->method('findByInputHash')->willReturn($cache);

        $apiClient = $this->createMock(BinPackingApiClient::class);
        $apiClient->expects(self::never())->method('findSmallestContainerId');

        $fallback = $this->createMock(FallbackPackingCalculator::class);
        $fallback->expects(self::never())->method('findSmallestContainerId');

        $service = new PackingService($packagingRepo, $cacheRepo, $apiClient, $fallback);
        $result = $service->findSmallestBox([new ProductInput(1, 1, 1, 1)]);

        self::assertSame(2, $result?->getId());
    }

    public function testFallsBackWhenApiUnavailable(): void
    {
        $box = $this->packaging(1, 4, 4, 4, 20);

        $packagingRepo = $this->createMock(PackagingRepository::class);
        $packagingRepo->method('findAll')->willReturn([$box]);
        $packagingRepo->method('findById')->willReturn($box);

        $cacheRepo = $this->createMock(PackingCacheRepository::class);
        $cacheRepo->method('findByInputHash')->willReturn(null);
        $cacheRepo->expects(self::never())->method('saveResult');

        $apiClient = $this->createMock(BinPackingApiClient::class);
        $apiClient->method('findSmallestContainerId')->willThrowException(new ApiUnavailableException());

        $fallback = $this->createMock(FallbackPackingCalculator::class);
        $fallback->method('findSmallestContainerId')->willReturn(1);

        $service = new PackingService($packagingRepo, $cacheRepo, $apiClient, $fallback);
        $result = $service->findSmallestBox([new ProductInput(1, 1, 1, 1)]);

        self::assertSame(1, $result?->getId());
    }

    private function packaging(int $id, float $w, float $h, float $l, float $maxWeight): Packaging
    {
        $packaging = new Packaging($w, $h, $l, $maxWeight);
        $idProperty = new ReflectionProperty(Packaging::class, 'id');
        $idProperty->setValue($packaging, $id);

        return $packaging;
    }
}
