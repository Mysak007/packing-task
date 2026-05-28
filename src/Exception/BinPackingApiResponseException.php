<?php

namespace App\Exception;

use Throwable;

final class BinPackingApiResponseException extends BinPackingApiException implements RecoverablePackingException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, null, $previous);
    }
}
