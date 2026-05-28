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
        $first = $width;
        $second = $height;
        $third = $length;

        if ($first > $second) {
            [$first, $second] = [$second, $first];
        }

        if ($second > $third) {
            [$second, $third] = [$third, $second];
        }

        if ($first > $second) {
            [$first, $second] = [$second, $first];
        }

        return [$first, $second, $third];
    }

}
