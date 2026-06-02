<?php declare(strict_types = 1);

namespace App\Util;

final class DimensionUtil
{

    /**
     * @return array{float, float, float}
     */
    public static function sort(
        float $width,
        float $height,
        float $length,
    ): array
    {
        $dimensions = [$width, $height, $length];
        sort($dimensions);

        return [$dimensions[0], $dimensions[1], $dimensions[2]];
    }

}
