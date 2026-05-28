<?php declare(strict_types = 1);

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Util\DimensionUtil;
use function array_map;
use function array_sum;
use function usort;

class FallbackPackingCalculator
{

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    public function findSmallestBox(
        array $products,
        array $boxes,
    ): ?int
    {
        $totalWeight = array_sum(array_map(static fn (ProductInput $product): float => $product->getWeight(), $products));
        $totalVolume = array_sum(array_map(static fn (ProductInput $product): float => $product->getWidth() * $product->getHeight() * $product->getLength(), $products));

        usort(
            $boxes,
            static fn (Packaging $left, Packaging $right): int => $left->getVolume() <=> $right->getVolume(),
        );

        foreach ($boxes as $box) {
            if ($totalWeight > $box->getMaxWeight()) {
                continue;
            }

            if ($totalVolume > $box->getVolume()) {
                continue;
            }

            if (!$this->everyProductFits($products, $box)) {
                continue;
            }

            return $box->getId();
        }

        return null;
    }

    /**
     * @param list<ProductInput> $products
     */
    private function everyProductFits(
        array $products,
        Packaging $box,
    ): bool
    {
        [$boxWidth, $boxHeight, $boxLength] = DimensionUtil::sort(
            $box->getWidth(),
            $box->getHeight(),
            $box->getLength(),
        );

        foreach ($products as $product) {
            [$productWidth, $productHeight, $productLength] = $product->getNormalizedDimensions();
            if ($productWidth > $boxWidth || $productHeight > $boxHeight || $productLength > $boxLength) {
                return false;
            }
        }

        return true;
    }

}
