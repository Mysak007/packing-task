<?php

namespace App\Exception;

final class BinPackingApiRateLimitedException extends BinPackingApiException implements RecoverablePackingException
{
    public function __construct(int $statusCode = 429)
    {
        parent::__construct('Packing API rate limit exceeded.', $statusCode);
    }
}
