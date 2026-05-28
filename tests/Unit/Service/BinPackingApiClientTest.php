<?php

namespace Tests\Unit\Service;

use App\DTO\ProductInput;
use App\Entity\Packaging;
use App\Exception\ApiUnavailableException;
use App\Service\BinPackingApiClient;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class BinPackingApiClientTest extends TestCase
{
    public function testSelectsSmallestContainerThatFitsAllItems(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'packedContainers' => [
                    ['containerId' => 'box-2', 'items' => [['itemId' => 'item-0']]],
                    ['containerId' => 'box-1', 'items' => [['itemId' => 'item-0']]],
                ],
                'unpackedItems' => [],
            ], JSON_THROW_ON_ERROR)),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $api = new BinPackingApiClient($client);

        $products = [new ProductInput(1, 1, 1, 1)];
        $boxes = [
            $this->packaging(1, 2, 2, 2, 10),
            $this->packaging(2, 3, 3, 3, 10),
        ];

        $result = $api->findSmallestContainerId($products, $boxes);
        self::assertSame(1, $result);
    }

    public function testRateLimitTriggersUnavailableException(): void
    {
        $mock = new MockHandler([
            new Response(429, [], '{"error":"rate_limit_exceeded"}'),
        ]);
        $client = new Client(['handler' => HandlerStack::create($mock)]);
        $api = new BinPackingApiClient($client);

        $this->expectException(ApiUnavailableException::class);
        $api->findSmallestContainerId([new ProductInput(1, 1, 1, 1)], [$this->packaging(1, 2, 2, 2, 10)]);
    }

    private function packaging(int $id, float $w, float $h, float $l, float $maxWeight): Packaging
    {
        $packaging = new Packaging($w, $h, $l, $maxWeight);
        $idProperty = new ReflectionProperty(Packaging::class, 'id');
        $idProperty->setValue($packaging, $id);

        return $packaging;
    }
}
