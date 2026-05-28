<?php declare(strict_types = 1);

namespace App\Exception;

use RuntimeException;

class ValidationException extends RuntimeException
{

    /**
     * @param list<array{path: string, message: string}> $violations
     */
    public function __construct(
        private readonly array $violations,
        string $message = 'Input validation failed',
    )
    {
        parent::__construct($message);
    }

    /**
     * @return list<array{path: string, message: string}>
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

}
