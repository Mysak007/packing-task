<?php

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;

class FallbackPackingCalculator
{
    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    public function findSmallestBox(array $products, array $boxes): ?int
    {
        $totalWeight = array_sum(array_map(static fn (ProductInput $product): float => $product->getWeight(), $products));
        $totalVolume = array_sum(array_map(static fn (ProductInput $product): float => $product->getWidth() * $product->getHeight() * $product->getLength(), $products));

        usort(
            $boxes,
            static fn (Packaging $left, Packaging $right): int => $left->getVolume() <=> $right->getVolume()
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
    private function everyProductFits(array $products, Packaging $box): bool
    {
        $boxDims = [$box->getWidth(), $box->getHeight(), $box->getLength()];
        sort($boxDims, SORT_NUMERIC);

        foreach ($products as $product) {
            $productDims = $product->getNormalizedDimensions();
            for ($i = 0; $i < 3; $i++) {
                if ($productDims[$i] > $boxDims[$i]) {
                    return false;
                }
            }
        }

        return true;
    }
}
