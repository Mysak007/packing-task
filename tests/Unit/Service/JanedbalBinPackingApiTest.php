<?php

namespace Tests\Unit\Service;

use App\Exception\BinPackingApiClientException;
use App\Exception\BinPackingApiRateLimitedException;
use App\Exception\BinPackingApiResponseException;
use App\Exception\BinPackingApiServerException;
use App\Exception\BinPackingApiTransportException;
use App\Service\JanedbalBinPackingApi;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class JanedbalBinPackingApiTest extends TestCase
{
    public function testPackReturnsDecodedPayload(): void
    {
        $mock = new MockHandler([
            new Response(200, [], '{"packedContainers":[],"unpackedItems":[]}'),
        ]);
        $api = new JanedbalBinPackingApi(new Client(['handler' => HandlerStack::create($mock)]));

        $payload = $api->pack(
            [['id' => 'box-1', 'width' => 1, 'length' => 1, 'depth' => 1, 'maxWeight' => 1]],
            [['id' => 'item-0', 'width' => 1, 'length' => 1, 'depth' => 1, 'weight' => 1]]
        );

        self::assertArrayHasKey('packedContainers', $payload);
    }

    public function testRateLimitThrowsRecoverableException(): void
    {
        $mock = new MockHandler([
            new Response(429, [], '{"error":"rate_limit_exceeded"}'),
        ]);
        $api = new JanedbalBinPackingApi(new Client(['handler' => HandlerStack::create($mock)]));

        $this->expectException(BinPackingApiRateLimitedException::class);
        $api->pack([], []);
    }

    public function testServerErrorThrowsRecoverableException(): void
    {
        $mock = new MockHandler([
            new Response(503, [], '{"error":"internal_error"}'),
        ]);
        $api = new JanedbalBinPackingApi(new Client(['handler' => HandlerStack::create($mock)]));

        $this->expectException(BinPackingApiServerException::class);
        $api->pack([], []);
    }

    public function testClientErrorThrowsNonRecoverableException(): void
    {
        $mock = new MockHandler([
            new Response(422, [], '{"error":"validation_failed"}'),
        ]);
        $api = new JanedbalBinPackingApi(new Client(['handler' => HandlerStack::create($mock)]));

        $this->expectException(BinPackingApiClientException::class);
        $api->pack([], []);
    }

    public function testInvalidJsonThrowsRecoverableResponseException(): void
    {
        $mock = new MockHandler([
            new Response(200, [], 'not-json'),
        ]);
        $api = new JanedbalBinPackingApi(new Client(['handler' => HandlerStack::create($mock)]));

        $this->expectException(BinPackingApiResponseException::class);
        $api->pack([], []);
    }

    public function testTransportFailureThrowsRecoverableException(): void
    {
        $mock = new MockHandler([
            new ConnectException('Connection refused', new Request('POST', 'test')),
        ]);
        $api = new JanedbalBinPackingApi(new Client(['handler' => HandlerStack::create($mock)]));

        $this->expectException(BinPackingApiTransportException::class);
        $api->pack([], []);
    }
}
