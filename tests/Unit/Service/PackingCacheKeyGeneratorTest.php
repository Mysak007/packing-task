<?php

namespace Tests\Unit\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Service\PackingCacheKeyGenerator;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class PackingCacheKeyGeneratorTest extends TestCase
{
    public function testSameProductsInDifferentOrderProduceSameHash(): void
    {
        $generator = new PackingCacheKeyGenerator();
        $boxes = [$this->packaging(1, 4, 4, 4, 20)];

        $hashA = $generator->generate(
            [new ProductInput(1, 2, 3, 1), new ProductInput(4, 5, 6, 2)],
            $boxes
        );
        $hashB = $generator->generate(
            [new ProductInput(4, 5, 6, 2), new ProductInput(1, 2, 3, 1)],
            $boxes
        );

        self::assertSame($hashA, $hashB);
    }

    public function testRotatedDimensionsProduceSameHash(): void
    {
        $generator = new PackingCacheKeyGenerator();
        $boxes = [$this->packaging(1, 4, 4, 4, 20)];

        $hashA = $generator->generate([new ProductInput(5, 2, 3, 1)], $boxes);
        $hashB = $generator->generate([new ProductInput(2, 5, 3, 1)], $boxes);

        self::assertSame($hashA, $hashB);
    }

    private function packaging(int $id, float $w, float $h, float $l, float $maxWeight): Packaging
    {
        $packaging = new Packaging($w, $h, $l, $maxWeight);
        $idProperty = new ReflectionProperty(Packaging::class, 'id');
        $idProperty->setValue($packaging, $id);

        return $packaging;
    }
}
