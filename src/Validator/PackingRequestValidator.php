<?php declare(strict_types = 1);

namespace App\Validator;

use App\DTO\ProductInput;
use App\Exception\BadRequestException;
use App\Exception\ValidationException;
use JsonException;
use Psr\Http\Message\RequestInterface;
use function array_values;
use function is_array;
use function is_float;
use function is_int;
use function json_decode;
use const JSON_THROW_ON_ERROR;

class PackingRequestValidator
{

    /**
     * @return list<ProductInput>
     */
    public function validate(RequestInterface $request): array
    {
        $rawBody = (string) $request->getBody();

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new BadRequestException('Invalid JSON: ' . $exception->getMessage(), 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new ValidationException([
                ['path' => '/', 'message' => 'Payload must be a JSON object.'],
            ]);
        }

        $products = $decoded['products'] ?? null;
        if (!is_array($products) || $products === []) {
            throw new ValidationException([
                ['path' => '/products', 'message' => 'Products must be a non-empty array.'],
            ]);
        }

        $violations = [];
        $parsedProducts = [];
        foreach (array_values($products) as $index => $product) {
            if (!is_array($product)) {
                $violations[] = [
                    'path' => '/products/' . $index,
                    'message' => 'Product must be an object.',
                ];
                continue;
            }

            /** @var array<string, mixed> $productData */
            $productData = $product;

            $width = $this->validatePositiveNumber($productData, 'width', $index, $violations);
            $height = $this->validatePositiveNumber($productData, 'height', $index, $violations);
            $length = $this->validatePositiveNumber($productData, 'length', $index, $violations);
            $weight = $this->validatePositiveNumber($productData, 'weight', $index, $violations);

            if ($width !== null && $height !== null && $length !== null && $weight !== null) {
                $parsedProducts[] = new ProductInput($width, $height, $length, $weight);
            }
        }

        if ($violations !== []) {
            throw new ValidationException($violations);
        }

        return $parsedProducts;
    }

    /**
     * @param array<string, mixed> $product
     * @param list<array{path: string, message: string}> $violations
     */
    private function validatePositiveNumber(
        array $product,
        string $field,
        int $index,
        array &$violations,
    ): ?float
    {
        $value = $product[$field] ?? null;
        if (!is_int($value) && !is_float($value)) {
            $violations[] = [
                'path' => '/products/' . $index . '/' . $field,
                'message' => 'Expected number.',
            ];

            return null;
        }

        if ($value <= 0) {
            $violations[] = [
                'path' => '/products/' . $index . '/' . $field,
                'message' => 'Expected positive number.',
            ];

            return null;
        }

        return (float) $value;
    }

}
