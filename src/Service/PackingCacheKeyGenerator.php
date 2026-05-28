<?php declare(strict_types = 1);

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Util\DimensionUtil;
use function array_map;
use function hash;
use function json_encode;
use function usort;
use const JSON_THROW_ON_ERROR;

final class PackingCacheKeyGenerator
{

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    public function generate(
        array $products,
        array $boxes,
    ): string
    {
        $normalizedProducts = array_map(static function (ProductInput $product): array {
            [$width, $height, $length] = $product->getNormalizedDimensions();

            return [$width, $height, $length, $product->getWeight()];
        }, $products);

        usort($normalizedProducts, static fn (array $left, array $right): int => $left <=> $right);

        $normalizedBoxes = array_map(static function (Packaging $box): array {
            [$width, $height, $length] = DimensionUtil::sort(
                $box->getWidth(),
                $box->getHeight(),
                $box->getLength(),
            );

            return [$width, $height, $length, $box->getMaxWeight()];
        }, $boxes);

        usort($normalizedBoxes, static fn (array $left, array $right): int => $left <=> $right);

        $canonical = [
            'products' => $normalizedProducts,
            'boxes' => $normalizedBoxes,
        ];

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
    }

}
