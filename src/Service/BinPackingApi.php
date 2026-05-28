<?php declare(strict_types = 1);

namespace App\Service;

/**
 * Abstraction over an external 3D bin packing API.
 * Allows swapping providers (e.g. janedbal.cz) without changing domain logic.
 */
interface BinPackingApi
{

    /**
     * @param list<array<string, mixed>> $containers
     * @param list<array<string, mixed>> $items
     * @return array<string, mixed> decoded API response body
     */
    public function pack(
        array $containers,
        array $items,
    ): array;

}
