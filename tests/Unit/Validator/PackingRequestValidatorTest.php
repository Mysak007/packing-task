<?php declare(strict_types = 1);

namespace Tests\Unit\Validator;

use App\Exception\BadRequestException;
use App\Exception\ValidationException;
use App\Validator\PackingRequestValidator;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use function json_encode;
use function reset;
use const JSON_THROW_ON_ERROR;

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
        $firstProduct = reset($products);
        self::assertNotFalse($firstProduct);
        self::assertSame(1.2, $firstProduct->getWidth());
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
            $violations = $exception->getViolations();
            self::assertNotEmpty($violations);
            $firstViolation = reset($violations);
            self::assertNotFalse($firstViolation);
            self::assertSame('/products/0/width', $firstViolation['path']);
        }
    }

}
