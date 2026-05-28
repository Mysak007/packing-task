<?php

namespace Tests\Unit\Validator;

use App\Exception\BadRequestException;
use App\Exception\ValidationException;
use App\Validator\PackingRequestValidator;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class PackingRequestValidatorTest extends TestCase
{
    public function testValidPayloadIsParsed(): void
    {
        $validator = new PackingRequestValidator();
        $request = new Request('POST', '/pack', [], json_encode([
            'products' => [
                ['width' => 1.2, 'height' => 3.4, 'length' => 5.6, 'weight' => 7.8],
            ],
        ], JSON_THROW_ON_ERROR));

        $products = $validator->validate($request);

        self::assertCount(1, $products);
        self::assertSame(1.2, $products[0]->getWidth());
    }

    public function testInvalidJsonThrowsBadRequest(): void
    {
        $validator = new PackingRequestValidator();
        $request = new Request('POST', '/pack', [], '{"products": [}');

        $this->expectException(BadRequestException::class);
        $validator->validate($request);
    }

    public function testInvalidProductsThrowsValidationException(): void
    {
        $validator = new PackingRequestValidator();
        $request = new Request('POST', '/pack', [], json_encode([
            'products' => [
                ['width' => -1, 'height' => 3.4, 'length' => 5.6, 'weight' => 7.8],
            ],
        ], JSON_THROW_ON_ERROR));

        try {
            $validator->validate($request);
            self::fail('ValidationException was expected.');
        } catch (ValidationException $exception) {
            self::assertNotEmpty($exception->getViolations());
            self::assertSame('/products/0/width', $exception->getViolations()[0]['path']);
        }
    }
}
