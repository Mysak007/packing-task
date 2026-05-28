<?php

namespace App\Infrastructure;

use Throwable;

final class ErrorLogger
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function log(Throwable $throwable, array $context = []): void
    {
        $payload = [
            'level' => 'error',
            'exception' => $throwable::class,
            'message' => $throwable->getMessage(),
            'context' => $context,
        ];

        error_log((string) json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
