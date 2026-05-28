<?php declare(strict_types = 1);

namespace Tests\Unit\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Exception\BinPackingApiResponseException;
use App\Service\BinPackingResponseSelector;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class BinPackingResponseSelectorTest extends TestCase
{

    public function testSelectsSmallestBoxThatFitsAllItems(): void
    {
        $selector = new BinPackingResponseSelector();
        $products = [new ProductInput(1, 1, 1, 1)];
        $boxes = [
            $this->packaging(1, 2, 2, 2, 10),
            $this->packaging(2, 3, 3, 3, 10),
        ];

        $result = $selector->findSmallestBox($products, $boxes, [
            'packedContainers' => [
                ['containerId' => 'box-2', 'items' => [['itemId' => 'item-0']]],
                ['containerId' => 'box-1', 'items' => [['itemId' => 'item-0']]],
            ],
        ]);

        self::assertSame(1, $result);
    }

    public function testMissingPackedContainersThrowsResponseException(): void
    {
        $selector = new BinPackingResponseSelector();

        $this->expectException(BinPackingApiResponseException::class);
        $selector->findSmallestBox([], [], []);
    }

    private function packaging(
        int $id,
        float $w,
        float $h,
        float $l,
        float $maxWeight,
    ): Packaging
    {
        $packaging = new Packaging($w, $h, $l, $maxWeight);
        $idProperty = new ReflectionProperty(Packaging::class, 'id');
        $idProperty->setValue($packaging, $id);

        return $packaging;
    }

}
