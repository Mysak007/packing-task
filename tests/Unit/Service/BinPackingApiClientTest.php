<?php

namespace Tests\Unit\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Service\BinPackingApi;
use App\Service\BinPackingApiClient;
use App\Service\BinPackingRequestMapper;
use App\Service\BinPackingResponseSelector;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class BinPackingApiClientTest extends TestCase
{
    public function testDelegatesToApiAndSelectsSmallestBox(): void
    {
        $products = [new ProductInput(1, 1, 1, 1)];
        $boxes = [$this->packaging(1, 2, 2, 2, 10)];

        $binPackingApi = $this->createMock(BinPackingApi::class);
        $binPackingApi->expects(self::once())
            ->method('pack')
            ->willReturn([
                'packedContainers' => [
                    ['containerId' => 'box-1', 'items' => [['itemId' => 'item-0']]],
                ],
            ]);

        $client = new BinPackingApiClient(
            $binPackingApi,
            new BinPackingRequestMapper(),
            new BinPackingResponseSelector()
        );
        $result = $client->findSmallestBox($products, $boxes);

        self::assertSame(1, $result);
    }

    private function packaging(int $id, float $w, float $h, float $l, float $maxWeight): Packaging
    {
        $packaging = new Packaging($w, $h, $l, $maxWeight);
        $idProperty = new ReflectionProperty(Packaging::class, 'id');
        $idProperty->setValue($packaging, $id);

        return $packaging;
    }
}
