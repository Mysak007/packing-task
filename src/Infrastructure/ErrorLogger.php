<?php declare(strict_types = 1);

namespace App\Infrastructure;

use Throwable;
use function error_log;
use function json_encode;
use const JSON_THROW_ON_ERROR;

final class ErrorLogger
{

    /**
     * @param array<string, scalar|null> $context
     */
    public function log(
        Throwable $throwable,
        array $context = [],
    ): void
    {
        $payload = [
            'level' => 'error',
            'exception' => $throwable::class,
            'message' => $throwable->getMessage(),
            'context' => $context,
        ];

        error_log(json_encode($payload, JSON_THROW_ON_ERROR));
    }

}
