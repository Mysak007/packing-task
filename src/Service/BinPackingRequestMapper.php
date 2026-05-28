<?php

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;

final class BinPackingRequestMapper
{
    private const SCALE = 1000;

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     *
     * @return array{
     *     containers: list<array<string, mixed>>,
     *     items: list<array<string, mixed>>
     * }
     */
    public function map(array $products, array $boxes): array
    {
        $containers = array_map(
            fn (Packaging $box): array => [
                'id' => $this->boxIdToExternalContainerId($box->getId()),
                'width' => $this->toApiInt($box->getWidth()),
                'length' => $this->toApiInt($box->getLength()),
                'depth' => $this->toApiInt($box->getHeight()),
                'maxWeight' => $this->toApiInt($box->getMaxWeight()),
            ],
            $boxes
        );

        $items = [];
        foreach ($products as $index => $product) {
            $items[] = [
                'id' => 'item-' . $index,
                'width' => $this->toApiInt($product->getWidth()),
                'length' => $this->toApiInt($product->getLength()),
                'depth' => $this->toApiInt($product->getHeight()),
                'weight' => $this->toApiInt($product->getWeight()),
            ];
        }

        return [
            'containers' => $containers,
            'items' => $items,
        ];
    }

    private function toApiInt(float $value): int
    {
        return max(1, (int) round($value * self::SCALE));
    }

    private function boxIdToExternalContainerId(?int $id): string
    {
        return 'box-' . (string) $id;
    }
}
