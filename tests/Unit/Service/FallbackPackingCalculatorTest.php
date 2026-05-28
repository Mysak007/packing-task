<?php declare(strict_types = 1);

namespace Tests\Unit\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Service\FallbackPackingCalculator;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class FallbackPackingCalculatorTest extends TestCase
{

    public function testReturnsSmallestFittingBox(): void
    {
        $service = new FallbackPackingCalculator();
        $products = [
            new ProductInput(2, 2, 2, 1),
        ];

        $small = $this->packaging(1, 2.5, 2.5, 2.5, 10);
        $big = $this->packaging(2, 5, 5, 5, 10);

        $result = $service->findSmallestBox($products, [$big, $small]);
        self::assertSame(1, $result);
    }

    public function testReturnsNullWhenWeightExceedsLimit(): void
    {
        $service = new FallbackPackingCalculator();
        $products = [new ProductInput(1, 1, 1, 100)];
        $box = $this->packaging(1, 10, 10, 10, 20);

        $result = $service->findSmallestBox($products, [$box]);
        self::assertNull($result);
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
