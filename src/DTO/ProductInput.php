<?php declare(strict_types = 1);

namespace App\DTO;

use App\Util\DimensionUtil;

class ProductInput
{

    public function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly float $length,
        private readonly float $weight,
    )
    {
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getLength(): float
    {
        return $this->length;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    /**
     * @return array{float, float, float}
     */
    public function getNormalizedDimensions(): array
    {
        return DimensionUtil::sort($this->width, $this->height, $this->length);
    }

}
