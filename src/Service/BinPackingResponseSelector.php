<?php declare(strict_types = 1);

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Exception\BinPackingApiResponseException;
use function count;
use function ctype_digit;
use function is_array;
use function is_string;
use function str_starts_with;
use function substr;

final class BinPackingResponseSelector
{

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     * @param array<string, mixed> $payload
     */
    public function findSmallestBox(
        array $products,
        array $boxes,
        array $payload,
    ): ?int
    {
        $packedContainers = $payload['packedContainers'] ?? null;
        if (!is_array($packedContainers)) {
            throw new BinPackingApiResponseException('Invalid API response shape: missing packedContainers.');
        }

        $targetItemsCount = count($products);
        $bestId = null;
        $bestVolume = null;

        foreach ($packedContainers as $packedContainer) {
            if (!is_array($packedContainer)) {
                continue;
            }

            $containerId = $packedContainer['containerId'] ?? null;
            $items = $packedContainer['items'] ?? null;
            if (!is_string($containerId)) {
                continue;
            }
            if (!is_array($items)) {
                continue;
            }
            if (count($items) !== $targetItemsCount) {
                continue;
            }

            $boxId = $this->externalContainerIdToBoxId($containerId);
            if ($boxId === null) {
                continue;
            }

            $box = $this->findPackagingById($boxes, $boxId);
            if (!$box instanceof Packaging) {
                continue;
            }

            if ($bestVolume === null || $box->getVolume() < $bestVolume) {
                $bestId = $boxId;
                $bestVolume = $box->getVolume();
            }
        }

        return $bestId;
    }

    private function externalContainerIdToBoxId(string $containerId): ?int
    {
        if (!str_starts_with($containerId, 'box-')) {
            return null;
        }

        $value = substr($containerId, 4);
        if ($value === '' || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param list<Packaging> $boxes
     */
    private function findPackagingById(
        array $boxes,
        int $id,
    ): ?Packaging
    {
        foreach ($boxes as $box) {
            if ($box->getId() === $id) {
                return $box;
            }
        }

        return null;
    }

}
