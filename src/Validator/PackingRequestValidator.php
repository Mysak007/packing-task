<?php

namespace App\Validator;

use App\DTO\ProductInput;
use App\Exception\BadRequestException;
use App\Exception\ValidationException;
use JsonException;
use Psr\Http\Message\RequestInterface;

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
        foreach ($products as $index => $product) {
            if (!is_array($product)) {
                $violations[] = [
                    'path' => '/products/' . $index,
                    'message' => 'Product must be an object.',
                ];
                continue;
            }

            $width = $this->validatePositiveNumber($product, 'width', $index, $violations);
            $height = $this->validatePositiveNumber($product, 'height', $index, $violations);
            $length = $this->validatePositiveNumber($product, 'length', $index, $violations);
            $weight = $this->validatePositiveNumber($product, 'weight', $index, $violations);

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
    private function validatePositiveNumber(array $product, string $field, int $index, array &$violations): ?float
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
