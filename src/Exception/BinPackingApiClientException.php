<?php

namespace App\Exception;

final class BinPackingApiClientException extends BinPackingApiException
{
    public function __construct(int $statusCode)
    {
        parent::__construct('Packing API rejected the request.', $statusCode);
    }
}
