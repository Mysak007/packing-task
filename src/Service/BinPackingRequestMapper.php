<?php declare(strict_types = 1);

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use LogicException;
use function array_map;
use function max;
use function round;

final class BinPackingRequestMapper
{

    private const int SCALE = 1000;

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     * @return array{
     *     containers: list<array<string, mixed>>,
     *     items: list<array<string, mixed>>
     * }
     */
    public function map(
        array $products,
        array $boxes,
    ): array
    {
        $containers = array_map(
            fn (Packaging $box): array => [
                'id' => $this->boxIdToExternalContainerId($this->requireBoxId($box)),
                'width' => $this->toApiInt($box->getWidth()),
                'length' => $this->toApiInt($box->getLength()),
                'depth' => $this->toApiInt($box->getHeight()),
                'maxWeight' => $this->toApiInt($box->getMaxWeight()),
            ],
            $boxes,
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

    private function requireBoxId(Packaging $box): int
    {
        $id = $box->getId();
        if ($id === null) {
            throw new LogicException('Warehouse box must have a persisted ID.');
        }

        return $id;
    }

    private function boxIdToExternalContainerId(int $id): string
    {
        return 'box-' . $id;
    }

}
