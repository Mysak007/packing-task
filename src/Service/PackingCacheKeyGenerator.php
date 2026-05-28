<?php

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;

final class PackingCacheKeyGenerator
{
    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    public function generate(array $products, array $boxes): string
    {
        $normalizedProducts = array_map(function (ProductInput $product): array {
            $dims = $product->getNormalizedDimensions();

            return [$dims[0], $dims[1], $dims[2], $product->getWeight()];
        }, $products);

        usort($normalizedProducts, static fn (array $left, array $right): int => $left <=> $right);

        $normalizedBoxes = array_map(function (Packaging $box): array {
            $dims = [$box->getWidth(), $box->getHeight(), $box->getLength()];
            sort($dims, SORT_NUMERIC);

            return [$dims[0], $dims[1], $dims[2], $box->getMaxWeight()];
        }, $boxes);

        usort($normalizedBoxes, static fn (array $left, array $right): int => $left <=> $right);

        $canonical = [
            'products' => $normalizedProducts,
            'boxes' => $normalizedBoxes,
        ];

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
    }
}
