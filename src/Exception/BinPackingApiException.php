<?php declare(strict_types = 1);

namespace App\Exception;

use RuntimeException;
use Throwable;

abstract class BinPackingApiException extends RuntimeException
{

    public function __construct(
        string $message,
        private readonly ?int $statusCode = null,
        ?Throwable $previous = null,
    )
    {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

}
