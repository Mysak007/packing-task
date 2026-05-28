<?php declare(strict_types = 1);

namespace App\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;

/**
 * Orchestrates external bin packing: maps domain input, calls API, selects smallest box.
 */
class BinPackingApiClient
{

    public function __construct(
        private readonly BinPackingApi $binPackingApi,
        private readonly BinPackingRequestMapper $requestMapper,
        private readonly BinPackingResponseSelector $responseSelector,
    )
    {
    }

    /**
     * @param list<ProductInput> $products
     * @param list<Packaging> $boxes
     */
    public function findSmallestBox(
        array $products,
        array $boxes,
    ): ?int
    {
        $request = $this->requestMapper->map($products, $boxes);
        $payload = $this->binPackingApi->pack($request['containers'], $request['items']);

        return $this->responseSelector->findSmallestBox($products, $boxes, $payload);
    }

}
