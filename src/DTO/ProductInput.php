<?php

namespace App\DTO;

class ProductInput
{
    public function __construct(
        private readonly float $width,
        private readonly float $height,
        private readonly float $length,
        private readonly float $weight
    ) {
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
     * @return list<float>
     */
    public function getNormalizedDimensions(): array
    {
        $dimensions = [$this->width, $this->height, $this->length];
        sort($dimensions, SORT_NUMERIC);

        return $dimensions;
    }

    /**
     * @return array{width: float, height: float, length: float, weight: float}
     */
    public function toArray(): array
    {
        return [
            'width' => $this->width,
            'height' => $this->height,
            'length' => $this->length,
            'weight' => $this->weight,
        ];
    }
}
